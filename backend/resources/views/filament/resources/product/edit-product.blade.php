<x-filament-panels::page>
    <form method="POST" action="{{ route('filament.admin.resources.products.update', $record->id) }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        @include('filament.resources.product.product-form', ['record' => $this->record])
        <div style="display:flex;gap:12px;justify-content:flex-end;margin-top:8px;">
            <a href="{{ \App\Filament\Resources\ProductResource::getUrl('view', ['record' => $record->id]) }}"
               style="padding:10px 22px;border-radius:8px;border:1px solid rgba(255,255,255,0.1);color:#94a3b8;text-decoration:none;font-size:14px;font-weight:600;">
                Cancel
            </a>
            <button type="submit"
                    style="padding:10px 28px;border-radius:8px;background:linear-gradient(135deg,#6366f1,#4f46e5);color:#fff;border:none;font-size:14px;font-weight:700;cursor:pointer;box-shadow:0 4px 14px rgba(99,102,241,0.35);">
                 Save Changes
            </button>
        </div>
    </form>
</x-filament-panels::page>
