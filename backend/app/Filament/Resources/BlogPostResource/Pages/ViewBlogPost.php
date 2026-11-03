<?php

namespace App\Filament\Resources\BlogPostResource\Pages;

use App\Filament\Resources\BlogPostResource;
use App\Models\BlogPost;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\Group;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\Split;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Enums\FontWeight;

class ViewBlogPost extends ViewRecord
{
    protected static string $resource = BlogPostResource::class;

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([

            // ── Hero Banner ───────────────────────────────────────────────
            Section::make()->schema([
                Split::make([
                    ImageEntry::make('featured_image')->label('')->width(320)->height(200)->extraImgAttributes(['class' => 'rounded-xl object-cover shadow'])->grow(false)->hiddenLabel()->visible(fn(BlogPost $record) => filled($record->featured_image)),
                    Group::make()->schema([
                        TextEntry::make('status')->label('')->badge()->formatStateUsing(fn(string $state) => match ($state) {
                            'published' => 'Published',
                            'draft'     => 'Draft',
                            'archived'  => 'Archived',
                            default     => $state,
                        })->color(fn(string $state) => match ($state) {
                            'published' => 'success',
                            'draft'     => 'warning',
                            'archived'  => 'gray',
                            default     => 'gray',
                        })->icon(fn(string $state) => match ($state) {
                            'published' => 'heroicon-o-check-circle',
                            'draft'     => 'heroicon-o-pencil',
                            'archived'  => 'heroicon-o-archive-box',
                            default     => null,
                        }),
                        TextEntry::make('title')->label('')->size(TextEntry\TextEntrySize::Large)->weight(FontWeight::Bold)->extraAttributes(['class' => 'text-2xl leading-snug']),
                        TextEntry::make('title_bn')->label('')->color('gray')->size(TextEntry\TextEntrySize::Medium)->visible(fn(BlogPost $record) => filled($record->title_bn)),
                        Grid::make(3)->schema([
                            TextEntry::make('category.name')->label('Category')->badge()->color('primary')->icon('heroicon-o-tag'),
                            TextEntry::make('author.name')->label('Author')->icon('heroicon-o-user-circle')->placeholder('—'),
                            TextEntry::make('published_at')->label('Published')->icon('heroicon-o-calendar-days')->dateTime('d M Y, h:i A')->placeholder('Not published'),
                        ]),

                        Grid::make(3)->schema([
                            TextEntry::make('view_count')->label('Views')->icon('heroicon-o-eye')->numeric()->badge()->color('info'),
                            TextEntry::make('comments_count')->label('Comments')->icon('heroicon-o-chat-bubble-left-right')->badge()->color('primary')->state(fn(BlogPost $record) => $record->comments()->count()),
                            IconEntry::make('is_featured')->label('Featured')->boolean()->trueIcon('heroicon-o-star')->falseIcon('heroicon-o-star')->trueColor('warning')->falseColor('gray'),
                        ]),
                    ])->grow(true),
                ])->from('md'),
            ])->columnSpanFull(),

            // ── Excerpt ───────────────────────────────────────────────────
            Section::make('Excerpt')->icon('heroicon-o-bars-3-bottom-left')->collapsible()->schema([
                TextEntry::make('excerpt')->label('English')->placeholder('No excerpt provided.')->columnSpanFull(),
                TextEntry::make('excerpt_bn')->label('Bengali')->placeholder('No excerpt available.')->columnSpanFull()->visible(fn(BlogPost $record) => filled($record->excerpt_bn)),
            ]),

            // ── Full Content ──────────────────────────────────────────────
            Section::make('Full Content')->icon('heroicon-o-document-text')->collapsible()->schema([
                TextEntry::make('content')->label('English')->html()->columnSpanFull()->extraAttributes([
                    'class' => 'prose prose-sm max-w-none dark:prose-invert',
                ]),

                TextEntry::make('content_bn')->label('Bengali Content')->html()->columnSpanFull()->visible(fn(BlogPost $record) => filled($record->content_bn))->extraAttributes([
                    'class' => 'prose prose-sm max-w-none dark:prose-invert',
                ]),
            ]),

            // ── Tags & Settings ───────────────────────────────────────────
            Section::make('Tags & Settings')->icon('heroicon-o-adjustments-horizontal')->collapsible()->columns(2)->schema([
                TextEntry::make('tags')->label('Tags')->badge()->color('secondary')->separator(',')->placeholder('No tags.')->columnSpanFull(),
                IconEntry::make('allow_comments')->label('Comments Allowed')->boolean()->trueIcon('heroicon-o-check-circle')->falseIcon('heroicon-o-x-circle')->trueColor('success')->falseColor('danger'),
                IconEntry::make('is_featured')->label('Featured Post')->boolean()->trueIcon('heroicon-o-star')->falseIcon('heroicon-o-star')->trueColor('warning')->falseColor('gray'),
            ]),

            // ── SEO ───────────────────────────────────────────────────────
            Section::make('SEO')->icon('heroicon-o-magnifying-glass')->collapsible()->collapsed()->columns(2)->schema([
                TextEntry::make('meta_title')->label('Meta Title')->placeholder('—')->copyable()->copyMessage('Copied!')->icon('heroicon-o-document-magnifying-glass'),
                TextEntry::make('slug')->label('Slug')->copyable()->copyMessage('Copied!')->icon('heroicon-o-link'),
                TextEntry::make('meta_description')->label('Meta Description')->placeholder('—')->columnSpanFull(),
            ]),

            // ── Timestamps ────────────────────────────────────────────────
            Section::make('Record Info')->icon('heroicon-o-information-circle')->collapsible()->collapsed()->columns(2)->schema([
                TextEntry::make('created_at')->label('Created At')->dateTime('d M Y, h:i A')->icon('heroicon-o-clock'),
                TextEntry::make('updated_at')->label('Last Updated')->dateTime('d M Y, h:i A')->icon('heroicon-o-arrow-path')->since(),
            ]),
        ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('publish')->label('Publish Now')->icon('heroicon-o-check-circle')->color('success')->visible(fn() => $this->record->status !== 'published')->requiresConfirmation()->modalHeading('Publish this post?')->modalDescription('This will make the post publicly visible on the site.')->action(function () {
                $this->record->update([
                    'status'       => 'published',
                    'published_at' => now(),
                ]);
                Notification::make()->success()->title('Post published successfully!')->send();
                $this->refreshFormData(['status', 'published_at']);
            }),

            Action::make('archive')->label('Archive')->icon('heroicon-o-archive-box')->color('gray')->visible(fn() => $this->record->status === 'published')->requiresConfirmation()->action(function () {
                $this->record->update(['status' => 'archived']);
                Notification::make()->warning()->title('Post archived.')->send();
                $this->refreshFormData(['status']);
            }),
            EditAction::make()->icon('heroicon-o-pencil-square'),
        ];
    }
}
