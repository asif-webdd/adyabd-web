<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DepartmentSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        // Departments
        $departments = [
            ['name' => 'Technology', 'name_bn' => 'প্রযুক্তি', 'head_employee_id' => null, 'description' => 'Software development and IT infrastructure.', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Operations', 'name_bn' => 'অপারেশন', 'head_employee_id' => null, 'description' => 'Order processing, logistics and fulfilment.', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Customer Service', 'name_bn' => 'গ্রাহক সেবা', 'head_employee_id' => null, 'description' => 'Customer support and dispute resolution.', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Marketing', 'name_bn' => 'মার্কেটিং', 'head_employee_id' => null, 'description' => 'Brand promotion, campaigns and social media.', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Finance', 'name_bn' => 'অর্থ', 'head_employee_id' => null, 'description' => 'Accounting, payroll and financial reporting.', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
        ];

        DB::table('departments')->insert($departments);

        $techId = DB::table('departments')->where('name', 'Technology')->value('id');
        $opsId = DB::table('departments')->where('name', 'Operations')->value('id');
        $csId = DB::table('departments')->where('name', 'Customer Service')->value('id');
        $mktId = DB::table('departments')->where('name', 'Marketing')->value('id');
        $finId = DB::table('departments')->where('name', 'Finance')->value('id');

        // Designations
        $designations = [
            ['department_id' => $techId, 'title' => 'Software Engineer', 'title_bn' => 'সফটওয়্যার ইঞ্জিনিয়ার', 'min_salary' => 40000, 'max_salary' => 80000, 'is_active' => true],
            ['department_id' => $techId, 'title' => 'Senior Software Engineer', 'title_bn' => 'সিনিয়র সফটওয়্যার ইঞ্জিনিয়ার', 'min_salary' => 70000, 'max_salary' => 130000, 'is_active' => true],
            ['department_id' => $techId, 'title' => 'DevOps Engineer', 'title_bn' => 'ডেভঅপস ইঞ্জিনিয়ার', 'min_salary' => 50000, 'max_salary' => 90000, 'is_active' => true],
            ['department_id' => $opsId, 'title' => 'Logistics Coordinator', 'title_bn' => 'লজিস্টিক্স সমন্বয়কারী', 'min_salary' => 20000, 'max_salary' => 40000, 'is_active' => true],
            ['department_id' => $opsId, 'title' => 'Warehouse Manager', 'title_bn' => 'গুদাম ব্যবস্থাপক', 'min_salary' => 30000, 'max_salary' => 55000, 'is_active' => true],
            ['department_id' => $csId, 'title' => 'Support Agent', 'title_bn' => 'সাপোর্ট এজেন্ট', 'min_salary' => 15000, 'max_salary' => 25000, 'is_active' => true],
            ['department_id' => $csId, 'title' => 'Team Lead — CS', 'title_bn' => 'টিম লিড — সিএস', 'min_salary' => 30000, 'max_salary' => 50000, 'is_active' => true],
            ['department_id' => $mktId, 'title' => 'Digital Marketing Specialist', 'title_bn' => 'ডিজিটাল মার্কেটিং বিশেষজ্ঞ', 'min_salary' => 25000, 'max_salary' => 50000, 'is_active' => true],
            ['department_id' => $finId, 'title' => 'Accountant', 'title_bn' => 'হিসাবরক্ষক', 'min_salary' => 20000, 'max_salary' => 40000, 'is_active' => true],
        ];

        foreach ($designations as &$d) {
            $d['created_at'] = $now;
            $d['updated_at'] = $now;
        }

        DB::table('designations')->insert($designations);

        // Employees
        $engDesigId = DB::table('designations')->where('title', 'Software Engineer')->value('id');
        $srEngDesigId = DB::table('designations')->where('title', 'Senior Software Engineer')->value('id');
        $opsDesigId = DB::table('designations')->where('title', 'Logistics Coordinator')->value('id');
        $csDesigId = DB::table('designations')->where('title', 'Support Agent')->value('id');
        $mktDesigId = DB::table('designations')->where('title', 'Digital Marketing Specialist')->value('id');

        $employees = [
            [
                'employee_id'         => 'EMP-0001',
                'user_id'             => null,
                'department_id'       => $techId,
                'designation_id'      => $srEngDesigId,
                'name'                => 'Rafiqul Islam',
                'name_bn'             => 'রফিকুল ইসলাম',
                'email'               => 'rafiqul@bazarbd.com',
                'phone'               => '01810000001',
                'emergency_phone'     => '01810000002',
                'address'             => 'House 10, Mirpur-12, Dhaka',
                'nid_number'          => '1234567891011',
                'date_of_birth'       => '1992-04-15',
                'gender'              => 'male',
                'blood_group'         => 'B+',
                'join_date'           => '2022-01-10',
                'resignation_date'    => null,
                'employment_type'     => 'full_time',
                'status'              => 'active',
                'basic_salary'        => 80000.00,
                'house_rent'          => 20000.00,
                'medical_allowance'   => 5000.00,
                'transport_allowance' => 3000.00,
                'bank_account'        => '1122334455',
                'bank_name'           => 'BRAC Bank',
                'bkash_number'        => '01810000001',
            ],
            [
                'employee_id'         => 'EMP-0002',
                'user_id'             => null,
                'department_id'       => $techId,
                'designation_id'      => $engDesigId,
                'name'                => 'Nasrin Akter',
                'name_bn'             => 'নাসরিন আক্তার',
                'email'               => 'nasrin@bazarbd.com',
                'phone'               => '01820000001',
                'emergency_phone'     => null,
                'address'             => 'Flat 4B, Dhanmondi, Dhaka',
                'nid_number'          => '9988776655443',
                'date_of_birth'       => '1996-09-22',
                'gender'              => 'female',
                'blood_group'         => 'A+',
                'join_date'           => '2023-03-01',
                'resignation_date'    => null,
                'employment_type'     => 'full_time',
                'status'              => 'active',
                'basic_salary'        => 55000.00,
                'house_rent'          => 15000.00,
                'medical_allowance'   => 3000.00,
                'transport_allowance' => 2000.00,
                'bank_account'        => null,
                'bank_name'           => null,
                'bkash_number'        => '01820000001',
            ],
            [
                'employee_id'         => 'EMP-0003',
                'user_id'             => null,
                'department_id'       => $opsId,
                'designation_id'      => $opsDesigId,
                'name'                => 'Kamal Hossain',
                'name_bn'             => 'কামাল হোসেন',
                'email'               => 'kamal@bazarbd.com',
                'phone'               => '01830000001',
                'emergency_phone'     => '01830000002',
                'address'             => 'Savar, Dhaka',
                'nid_number'          => '5544332211009',
                'date_of_birth'       => '1990-12-05',
                'gender'              => 'male',
                'blood_group'         => 'O+',
                'join_date'           => '2021-07-15',
                'resignation_date'    => null,
                'employment_type'     => 'full_time',
                'status'              => 'active',
                'basic_salary'        => 28000.00,
                'house_rent'          => 8000.00,
                'medical_allowance'   => 1500.00,
                'transport_allowance' => 2000.00,
                'bank_account'        => '9988776655',
                'bank_name'           => 'Islami Bank',
                'bkash_number'        => '01830000001',
            ],
            [
                'employee_id'         => 'EMP-0004',
                'user_id'             => null,
                'department_id'       => $csId,
                'designation_id'      => $csDesigId,
                'name'                => 'Sadia Rahman',
                'name_bn'             => 'সাদিয়া রহমান',
                'email'               => 'sadia@bazarbd.com',
                'phone'               => '01840000001',
                'emergency_phone'     => null,
                'address'             => 'Uttara, Dhaka',
                'nid_number'          => '6677889900112',
                'date_of_birth'       => '1998-06-18',
                'gender'              => 'female',
                'blood_group'         => 'AB+',
                'join_date'           => '2023-09-01',
                'resignation_date'    => null,
                'employment_type'     => 'full_time',
                'status'              => 'active',
                'basic_salary'        => 18000.00,
                'house_rent'          => 5000.00,
                'medical_allowance'   => 1000.00,
                'transport_allowance' => 1500.00,
                'bank_account'        => null,
                'bank_name'           => null,
                'bkash_number'        => '01840000001',
            ],
            [
                'employee_id'         => 'EMP-0005',
                'user_id'             => null,
                'department_id'       => $mktId,
                'designation_id'      => $mktDesigId,
                'name'                => 'Tanvir Ahmed',
                'name_bn'             => 'তানভীর আহমেদ',
                'email'               => 'tanvir@bazarbd.com',
                'phone'               => '01850000001',
                'emergency_phone'     => '01850000002',
                'address'             => 'Gulshan-2, Dhaka',
                'nid_number'          => '3344556677889',
                'date_of_birth'       => '1994-02-28',
                'gender'              => 'male',
                'blood_group'         => 'B-',
                'join_date'           => '2022-11-01',
                'resignation_date'    => null,
                'employment_type'     => 'full_time',
                'status'              => 'active',
                'basic_salary'        => 40000.00,
                'house_rent'          => 12000.00,
                'medical_allowance'   => 2500.00,
                'transport_allowance' => 2500.00,
                'bank_account'        => '4455667788',
                'bank_name'           => 'Dutch Bangla Bank',
                'bkash_number'        => '01850000001',
            ],
        ];

        foreach ($employees as &$e) {
            $e['avatar'] = null;
            $e['created_at'] = $now;
            $e['updated_at'] = $now;
        }

        DB::table('employees')->insert($employees);

        $this->command->info('DepartmentSeeder: 5 departments, 9 designations, 5 employees seeded.');
    }
}
