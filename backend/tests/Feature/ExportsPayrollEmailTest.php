<?php

namespace Tests\Feature;

use App\Filament\Resources\OrderResource\Pages\ListOrders;
use App\Models\{AdminUser, Attendance, Employee, Leave, Order, Payroll, SupportTicket, User};
use App\Services\PayrollService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\{Http, Notification};
use Livewire\Livewire;
use Tests\TestCase;

class ExportsPayrollEmailTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        Http::fake(['*' => Http::response(['data' => [['status' => 'ACCEPTED']]], 200)]);
    }

    private function desig(): int
    {
        return \App\Models\Designation::first()?->id ?? \App\Models\Designation::create(['name' => 'Staff'])->id;
    }

    private function dept(): int
    {
        return \App\Models\Department::first()?->id ?? \App\Models\Department::create(['name' => 'Ops'])->id;
    }

    public function test_orders_csv_export_downloads(): void
    {
        $this->actingAs(AdminUser::first(), 'admin');
        Livewire::test(ListOrders::class)->callTableAction('export_csv')->assertFileDownloaded();
    }

    public function test_csv_cells_are_protected_against_formula_injection(): void
    {
        $cell = new \ReflectionMethod(\App\Filament\Support\CsvExport::class, 'cell');
        $cell->setAccessible(true);
        $row = (object) ['a' => '=HYPERLINK("http://evil")', 'b' => 'normal', 'c' => '-5', 'd' => '+8801711', 'e' => '+cmd|\' /C calc\'!A0'];
        $this->assertSame("'=HYPERLINK(\"http://evil\")", $cell->invoke(null, $row, 'a'));
        $this->assertSame('normal', $cell->invoke(null, $row, 'b'));
        $this->assertSame('-5', $cell->invoke(null, $row, 'c'), 'plain negative numbers stay numbers');
        $this->assertSame('+8801711', $cell->invoke(null, $row, 'd'), 'phone numbers stay numbers');
        $this->assertStringStartsWith("'+cmd", $cell->invoke(null, $row, 'e'));
    }

    public function test_payroll_generation_uses_attendance_and_leave(): void
    {
        $e = Employee::create(['department_id' => $this->dept(), 'designation_id' => $this->desig(), 'name' => 'Test Emp', 'phone' => '01700000099', 'join_date' => '2026-01-01', 'status' => 'active',
            'basic_salary' => 26000, 'house_rent' => 0, 'medical_allowance' => 0, 'transport_allowance' => 0, 'bkash_number' => '01700000099']);
        $svc = app(PayrollService::class);

        // 2026-09: 30 days, 4 Fridays => 26 working days.  Present 20 days, 2 days paid (casual) leave => 4 absent days.
        $this->assertSame(26, $svc->workingDays(\Carbon\Carbon::create(2026, 9, 1), \Carbon\Carbon::create(2026, 9, 30)));
        $n = 0;
        for ($d = \Carbon\Carbon::create(2026, 9, 1); $d->lte(\Carbon\Carbon::create(2026, 9, 30)) && $n < 20; $d->addDay()) {
            if (!$d->isFriday() && $d->day !== 7 && $d->day !== 8) {   // keep 7th & 8th (Mon/Tue) free for leave
                Attendance::create(['employee_id' => $e->id, 'date' => $d->toDateString(), 'status' => 'present']);
                $n++;
            }
        }
        Leave::create(['employee_id' => $e->id, 'type' => 'casual', 'from_date' => '2026-09-07', 'to_date' => '2026-09-08', 'days' => 2, 'reason' => 'x', 'status' => 'approved']);

        $r = $svc->generate(9, 2026);
        $p = Payroll::where('employee_id', $e->id)->where('month', 9)->firstOrFail();
        $this->assertSame(20, $p->present_days);
        $this->assertSame(2, $p->leave_days);
        $this->assertSame(4, $p->absent_days);
        $this->assertEqualsWithDelta(26000 - 4000, (float) $p->net_salary, 0.01, '26000/26 x 4 absent = 4000 deducted');
        $this->assertSame('bkash', $p->payment_method);
        $this->assertGreaterThan(0, $r['created']);

        $again = $svc->generate(9, 2026);
        $this->assertSame(0, $again['created'], 'second run creates nothing');
        $this->assertSame(1, Payroll::where('employee_id', $e->id)->where('month', 9)->count());
    }

    public function test_no_attendance_data_means_no_deduction(): void
    {
        $e = Employee::create(['department_id' => $this->dept(), 'designation_id' => $this->desig(), 'name' => 'No Att', 'phone' => '01700000098', 'join_date' => '2026-01-01', 'status' => 'active', 'basic_salary' => 30000]);
        app(PayrollService::class)->generate(8, 2026);
        $this->assertEqualsWithDelta(30000, (float) Payroll::where('employee_id', $e->id)->value('net_salary'), 0.01);
    }

    public function test_order_and_ticket_emails_go_to_customers_with_email(): void
    {
        Notification::fake();
        $user = User::where('phone', '01711000001')->firstOrFail();
        $user->update(['email' => 'cust@example.com']);
        $order = Order::where('user_id', $user->id)->first() ?? Order::first();
        $order->setRelation('user', $user);
        $order->user_id = $user->id;

        $order->emailCustomer('shipped');
        Notification::assertSentTo($user, \App\Notifications\CustomerMailNotification::class);

        $ticket = SupportTicket::create(['user_id' => $user->id, 'subject' => 's', 'category' => 'order', 'priority' => 'medium', 'status' => 'open']);
        $ticket->addAdminReply('Hello', AdminUser::first()->id);
        Notification::assertSentToTimes($user, \App\Notifications\CustomerMailNotification::class, 2);
    }

    public function test_no_email_when_customer_has_none_or_toggle_off(): void
    {
        Notification::fake();
        $user = User::where('phone', '01711000001')->firstOrFail();
        $user->update(['email' => null]);
        $order = Order::first(); $order->setRelation('user', $user);
        $order->emailCustomer('shipped');
        Notification::assertNothingSent();

        $user->update(['email' => 'c@example.com']);
        \App\Models\Setting::set('email_on_order_status', '');
        \Illuminate\Support\Facades\Cache::flush();
        $order->emailCustomer('shipped');
        Notification::assertNothingSent();
    }
}
