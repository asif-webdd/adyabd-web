@php
    use App\Models\{Category, Seller, Brand};
    $isEdit     = isset($record);
    $categories = Category::where('is_active', true)->orderBy('name')->get();
    $sellers    = Seller::where('status', 'active')->orderBy('shop_name')->get();
    $brands     = Brand::orderBy('name')->get();

    // Pre-fill values for edit
    $v = fn(string $key, $default = '') => old($key, $isEdit ? data_get($record, $key) : $default);
    $checked = fn(string $key, bool $default = false): bool => (bool) old($key, $isEdit ? $record->$key : $default);
@endphp

{{-- ══════════════════════════════════════════════════════════════ --}}
{{-- Shared styles + JS                                           --}}
{{-- ══════════════════════════════════════════════════════════════ --}}
<style>
    .pf { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; color: #e2e8f0; }
    .pf-card { background: rgba(255,255,255,0.025); border: 1px solid rgba(255,255,255,0.08); border-radius: 14px; padding: 24px; margin-bottom: 24px; }
    .pf-legend { font-size: 12px; font-weight: 700; color: #6366f1; text-transform: uppercase; letter-spacing: .08em; margin-bottom: 18px; display: flex; align-items: center; gap: 8px; }
    .pf-legend::after { content: ''; flex: 1; height: 1px; background: rgba(255,255,255,0.07); }
    .pf-grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
    .pf-grid-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 16px; }
    .pf-grid-6 { display: grid; grid-template-columns: repeat(6,1fr); gap: 12px; }
    .pf-full  { grid-column: 1 / -1; }
    .pf-label { font-size: 12px; font-weight: 600; color: #94a3b8; margin-bottom: 6px; display: block; letter-spacing: .02em; }
    .pf-hint  { font-size: 11px; color: #ddd; margin-top: 4px; }
    .pf-input {
        width: 100%; background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.1);
        border-radius: 8px; padding: 9px 12px; color: #e2e8f0; font-size: 14px;
        transition: border-color .15s, box-shadow .15s; outline: none; box-sizing: border-box;
    }
    .pf-input:focus { border-color: #6366f1; box-shadow: 0 0 0 3px rgba(99,102,241,0.15); }
    .pf-input::placeholder { color: #334155; }
    .pf-prefix-wrap { position: relative; }
    .pf-prefix { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #ddd; font-size: 14px; pointer-events: none; }
    .pf-prefix-wrap .pf-input { padding-left: 28px; }
    .pf-suffix-wrap { position: relative; }
    .pf-suffix { position: absolute; right: 12px; top: 50%; transform: translateY(-50%); color: #ddd; font-size: 13px; }
    .pf-suffix-wrap .pf-input { padding-right: 36px; }
    .pf-select { appearance: none; background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%236366f1' stroke-width='2'%3E%3Cpath d='M6 9l6 6 6-6'/%3E%3C/svg%3E"); background-repeat: no-repeat; background-position: right 12px center; }
    .pf-textarea { resize: vertical; min-height: 90px; }
    .pf-toggle-row { display: flex; align-items: center; justify-content: space-between; padding: 14px 16px; background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.07); border-radius: 10px; }
    .pf-toggle-label { font-size: 13px; font-weight: 600; color: #cbd5e1; }
    .pf-toggle-sub   { font-size: 11px; color: #ddd; margin-top: 2px; }
    .pf-switch { position: relative; width: 44px; height: 24px; flex-shrink: 0; }
    .pf-switch input { opacity: 0; width: 0; height: 0; }
    .pf-slider { position: absolute; inset: 0; background: rgba(255,255,255,0.1); border-radius: 99px; cursor: pointer; transition: .2s; }
    .pf-slider:before { content: ''; position: absolute; height: 18px; width: 18px; left: 3px; top: 3px; background: #fff; border-radius: 50%; transition: .2s; }
    .pf-switch input:checked + .pf-slider { background: #6366f1; }
    .pf-switch input:checked + .pf-slider:before { transform: translateX(20px); }
    .pf-img-drop { border: 2px dashed rgba(255,255,255,0.12); border-radius: 10px; padding: 32px; text-align: center; cursor: pointer; transition: border-color .15s; }
    .pf-img-drop:hover { border-color: #6366f1; }
    .pf-img-grid { display: grid; grid-template-columns: repeat(auto-fill,minmax(120px,1fr)); gap: 12px; margin-top: 16px; }
    .pf-img-thumb { position: relative; aspect-ratio: 1/1; border-radius: 8px; overflow: hidden; border: 2px solid rgba(255,255,255,0.1); }
    .pf-img-thumb img { width: 100%; height: 100%; object-fit: cover; display: block; }
    .pf-img-thumb-del { position: absolute; top: 4px; right: 4px; background: rgba(239,68,68,0.9); border: none; border-radius: 50%; width: 22px; height: 22px; color: #fff; font-size: 13px; cursor: pointer; display: flex; align-items: center; justify-content: center; }
    .pf-variant-card { background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.08); border-radius: 10px; padding: 16px; margin-bottom: 12px; }
    .pf-attr-row { display: grid; grid-template-columns: 1fr 1fr auto; gap: 8px; align-items: end; margin-bottom: 8px; }
    .pf-tag { display: inline-flex; align-items: center; gap: 5px; background: rgba(99,102,241,0.15); color: #818cf8; border: 1px solid rgba(99,102,241,0.25); padding: 3px 10px; border-radius: 6px; font-size: 12px; font-weight: 600; }
    .pf-tag button { background: none; border: none; color: #818cf8; cursor: pointer; font-size: 14px; line-height: 1; padding: 0; }
    .pf-tags-input { display: flex; flex-wrap: wrap; gap: 6px; background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.1); border-radius: 8px; padding: 8px 12px; min-height: 42px; cursor: text; }
    .pf-tags-input input { background: none; border: none; outline: none; color: #e2e8f0; font-size: 14px; flex: 1; min-width: 80px; }
    .pf-btn-add { display: inline-flex; align-items: center; gap: 6px; background: rgba(99,102,241,0.12); color: #818cf8; border: 1px dashed rgba(99,102,241,0.3); border-radius: 8px; padding: 8px 14px; font-size: 13px; font-weight: 600; cursor: pointer; transition: background .15s; margin-top: 10px; }
    .pf-btn-add:hover { background: rgba(99,102,241,0.2); }
    .pf-btn-del { background: rgba(239,68,68,0.1); color: #f87171; border: 1px solid rgba(239,68,68,0.2); border-radius: 6px; padding: 6px 12px; font-size: 12px; cursor: pointer; }
    .pf-richtext { background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.1); border-radius: 8px; min-height: 150px; padding: 12px; color: #e2e8f0; font-size: 14px; outline: none; }
    .pf-richtext:focus { border-color: #6366f1; }
    @media (max-width: 768px) {
        .pf-grid-2, .pf-grid-3, .pf-grid-6 { grid-template-columns: 1fr; }
    }
</style>

<div class="pf" x-data="productForm()" x-init="init()">

    {{-- ══════════════════════════════════════════════════════════════
         BASIC INFORMATION
    ══════════════════════════════════════════════════════════════ --}}
    <div class="pf-card">
        <div class="pf-legend"> Basic Information</div>
        <div class="pf-grid-2">
            <div>
                <label class="pf-label">Product Name <span style="color:#ef4444">*</span></label>
                <input class="pf-input" type="text" name="name" value="{{ $v('name') }}"
                       x-on:blur="if(!slug.length || autoSlug) { slug = slugify($event.target.value); autoSlug=true; }"
                       required placeholder="e.g. Premium Cotton T-Shirt">
            </div>
            <div>
                <label class="pf-label">Name (Bangla)</label>
                <input class="pf-input" type="text" name="name_bn" value="{{ $v('name_bn') }}" placeholder="Bengali Name">
            </div>
            <div>
                <label class="pf-label">Slug <span style="color:#ef4444">*</span></label>
                <input class="pf-input" type="text" name="slug" x-model="slug" required placeholder="auto-generated">
            </div>
            <div>
                <label class="pf-label">SKU</label>
                <input class="pf-input" type="text" name="sku" value="{{ $v('sku') }}" placeholder="e.g. PROD-001">
            </div>
            <div>
                <label class="pf-label">Category <span style="color:#ef4444">*</span></label>
                <select class="pf-input pf-select" name="category_id" required>
                    <option value="">— Select Category —</option>
                    @foreach ($categories as $cat)
                        <option value="{{ $cat->id }}" {{ $v('category_id') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="pf-label">Seller <span style="color:#ef4444">*</span></label>
                <select class="pf-input pf-select" name="seller_id" required>
                    <option value="">— Select Seller —</option>
                    @foreach ($sellers as $sel)
                        <option value="{{ $sel->id }}" {{ $v('seller_id') == $sel->id ? 'selected' : '' }}>{{ $sel->shop_name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="pf-label">Brand</label>
                <select class="pf-input pf-select" name="brand_id">
                    <option value="">— No Brand —</option>
                    @foreach ($brands as $brand)
                        <option value="{{ $brand->id }}" {{ $v('brand_id') == $brand->id ? 'selected' : '' }}>{{ $brand->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="pf-label">Video URL</label>
                <input class="pf-input" type="url" name="video_url" value="{{ $v('video_url') }}" placeholder="https://youtube.com/...">
            </div>
            <div class="pf-full">
                <label class="pf-label">Tags</label>
                <div class="pf-tags-input" id="tags-container" onclick="document.getElementById('tag-input').focus()">
                    <template x-for="(tag, i) in tags" :key="i">
                        <span class="pf-tag">
                            <span x-text="tag"></span>
                            <button type="button" @click="tags.splice(i,1); syncTags()">×</button>
                            <input type="hidden" name="tags[]" :value="tag">
                        </span>
                    </template>
                    <input id="tag-input" type="text" placeholder="Add tag, press Enter…"
                           @keydown.enter.prevent="addTag($event.target.value); $event.target.value=''"
                           @keydown.comma.prevent="addTag($event.target.value); $event.target.value=''">
                </div>
            </div>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════════════
         PRICING & STOCK
    ══════════════════════════════════════════════════════════════ --}}
    <div class="pf-card">
        <div class="pf-legend"> Pricing & Stock</div>
        <div class="pf-grid-3">
            <div>
                <label class="pf-label">Selling Price <span style="color:#ef4444">*</span></label>
                <div class="pf-prefix-wrap"><span class="pf-prefix">BDT </span>
                    <input class="pf-input" type="number" name="price" value="{{ $v('price') }}" step="0.01" min="0" required placeholder="0.00">
                </div>
            </div>
            <div>
                <label class="pf-label">Compare Price (MRP)</label>
                <div class="pf-prefix-wrap"><span class="pf-prefix">BDT </span>
                    <input class="pf-input" type="number" name="compare_price" value="{{ $v('compare_price') }}" step="0.01" min="0" placeholder="0.00">
                </div>
            </div>
            <div>
                <label class="pf-label">Cost / Buying Price</label>
                <div class="pf-prefix-wrap"><span class="pf-prefix">BDT </span>
                    <input class="pf-input" type="number" name="cost_price" value="{{ $v('cost_price') }}" step="0.01" min="0" placeholder="0.00">
                </div>
                <p class="pf-hint">Admin & seller only — used for profit calculations</p>
            </div>
            <div>
                <label class="pf-label">Stock Quantity <span style="color:#ef4444">*</span></label>
                <input class="pf-input" type="number" name="quantity" value="{{ $v('quantity', 0) }}" min="0" required>
            </div>
            <div>
                <label class="pf-label">Low Stock Alert</label>
                <input class="pf-input" type="number" name="low_stock_threshold" value="{{ $v('low_stock_threshold', 5) }}" min="0">
                <p class="pf-hint">Alert when stock falls below this</p>
            </div>
            <div>
                <label class="pf-label">Unit</label>
                <input class="pf-input" type="text" name="unit" value="{{ $v('unit', 'pcs') }}" placeholder="pcs, kg, litre…">
            </div>
            <div>
                <label class="pf-label">Weight</label>
                <div class="pf-suffix-wrap"><span class="pf-suffix">kg</span>
                    <input class="pf-input" type="number" name="weight" value="{{ $v('weight') }}" step="0.01" min="0" placeholder="0.00">
                </div>
            </div>
        </div>

        {{-- Live profit preview --}}
        <div style="margin-top:18px;padding:14px 18px;background:rgba(99,102,241,0.06);border:1px solid rgba(99,102,241,0.15);border-radius:10px;display:flex;gap:32px;flex-wrap:wrap;" x-show="priceCalc.price > 0">
            <div>
                <div style="font-size:10px;color:#6366f1;text-transform:uppercase;letter-spacing:.07em;margin-bottom:4px;">Profit / Unit</div>
                <div style="font-size:18px;font-weight:800;color:#10b981;" x-text="'BDT ' + priceCalc.profit"></div>
            </div>
            <div>
                <div style="font-size:10px;color:#6366f1;text-transform:uppercase;letter-spacing:.07em;margin-bottom:4px;">Margin</div>
                <div style="font-size:18px;font-weight:800;color:#f59e0b;" x-text="priceCalc.margin + '%'"></div>
            </div>
            <div>
                <div style="font-size:10px;color:#6366f1;text-transform:uppercase;letter-spacing:.07em;margin-bottom:4px;">Discount</div>
                <div style="font-size:18px;font-weight:800;color:#ef4444;" x-text="priceCalc.discount + '%'"></div>
            </div>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════════════
         DESCRIPTION
    ══════════════════════════════════════════════════════════════ --}}
    <div class="pf-card">
        <div class="pf-legend"> Description</div>
        <div style="display:flex;flex-direction:column;gap:16px;">
            <div>
                <label class="pf-label">Short Description</label>
                <textarea class="pf-input pf-textarea" name="short_description" rows="3" maxlength="500" placeholder="Brief summary shown in product listings…">{{ $v('short_description') }}</textarea>
                <p class="pf-hint">Max 500 characters</p>
            </div>
            <div>
                <label class="pf-label">Full Description</label>
                <div class="pf-richtext" contenteditable="true" id="desc-editor" x-ref="descEditor"
                     style="min-height:180px;">{!! $isEdit ? $record->description : '' !!}</div>
                <input type="hidden" name="description" x-ref="descInput" value="{{ $v('description') }}">
            </div>
            <div>
                <label class="pf-label">Full Description (Bangla)</label>
                <div class="pf-richtext" contenteditable="true" id="desc-bn-editor"
                     style="min-height:120px;">{!! $isEdit ? $record->description_bn : '' !!}</div>
                <input type="hidden" name="description_bn" x-ref="descBnInput" value="{{ $v('description_bn') }}">
            </div>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════════════
         IMAGES
    ══════════════════════════════════════════════════════════════ --}}
    <div class="pf-card">
        <div class="pf-legend"> Product Images</div>

        {{-- Existing images (edit mode) --}}
        @if ($isEdit && $record->images->count())
            <div style="margin-bottom:16px;">
                <p class="pf-hint" style="margin-bottom:10px;">Existing images — check to delete</p>
                <div class="pf-img-grid">
                    @foreach ($record->images as $img)
                        @php $url = str_starts_with($img->image_path,'http') ? $img->image_path : storage_url($img->image_path); @endphp
                        <div class="pf-img-thumb" style="{{ $img->is_primary ? 'border-color:#6366f1;' : '' }}">
                            <img src="{{ $url }}" alt="">
                            <label style="position:absolute;bottom:0;left:0;right:0;background:rgba(0,0,0,0.7);display:flex;align-items:center;justify-content:center;gap:4px;padding:4px;cursor:pointer;font-size:11px;color:#f87171;">
                                <input type="checkbox" name="delete_images[]" value="{{ $img->id }}" style="accent-color:#ef4444;"> Delete
                            </label>
                            @if ($img->is_primary)
                                <div style="position:absolute;top:4px;left:4px;background:#6366f1;color:#fff;font-size:9px;font-weight:700;padding:2px 6px;border-radius:4px;">PRIMARY</div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <div class="pf-img-drop" id="drop-zone"
             onclick="document.getElementById('img-upload').click()"
             ondragover="event.preventDefault();this.style.borderColor='#6366f1'"
             ondragleave="this.style.borderColor=''"
             ondrop="handleDrop(event)">
            <div style="font-size:32px;margin-bottom:8px;"></div>
            <div style="font-size:14px;font-weight:600;color:#94a3b8;">Drop images here or click to browse</div>
            <div style="font-size:12px;color:#ddd;margin-top:4px;">PNG, JPG, WEBP · Max 2MB each · Up to 8 images</div>
        </div>
        <input type="file" id="img-upload" name="new_images[]" multiple accept="image/*" style="display:none" onchange="previewImages(this)">

        <div class="pf-img-grid" id="new-img-preview"></div>

        <div style="margin-top:14px;">
            <label class="pf-label">Set Primary Image (index of new uploads, 0-based)</label>
            <input class="pf-input" type="number" name="primary_image_index" value="0" min="0" style="width:120px;">
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════════════
         VARIANTS
    ══════════════════════════════════════════════════════════════ --}}
    <div class="pf-card">
        <div class="pf-legend"> Product Variants</div>
        <div class="pf-toggle-row" style="margin-bottom:18px;">
            <div>
                <div class="pf-toggle-label">Has Variants</div>
                <div class="pf-toggle-sub">Enable size, color, or other variant options</div>
            </div>
            <label class="pf-switch">
                <input type="checkbox" name="has_variants" value="1" x-model="hasVariants" @change="if(hasVariants && variants.length===0) addVariant()">
                <span class="pf-slider"></span>
            </label>
        </div>

        <div x-show="hasVariants" x-transition>
            <template x-for="(variant, vi) in variants" :key="vi">
                <div class="pf-variant-card">
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px;">
                        <span style="font-size:13px;font-weight:700;color:#818cf8;" x-text="'Variant ' + (vi+1)"></span>
                        <button type="button" class="pf-btn-del" @click="variants.splice(vi,1)"> Remove</button>
                    </div>

                    {{-- Attributes --}}
                    <div style="margin-bottom:14px;">
                        <label class="pf-label">Attributes</label>
                        <template x-for="(attr, ai) in variant.attribute_values" :key="ai">
                            <div class="pf-attr-row">
                                <div>
                                    <input class="pf-input" type="text" :name="'variants['+vi+'][attribute_values]['+ai+'][attribute]'"
                                           x-model="attr.attribute" placeholder="Attribute (Color, Size…)">
                                </div>
                                <div>
                                    <input class="pf-input" type="text" :name="'variants['+vi+'][attribute_values]['+ai+'][value]'"
                                           x-model="attr.value" placeholder="Value (Red, XL…)">
                                </div>
                                <button type="button" class="pf-btn-del" @click="variant.attribute_values.splice(ai,1)"></button>
                            </div>
                        </template>
                        <button type="button" class="pf-btn-add" @click="variant.attribute_values.push({attribute:'',value:''})">+ Add Attribute</button>
                    </div>

                    {{-- Variant fields --}}
                    <div class="pf-grid-3">
                        <div>
                            <input type="hidden" :name="'variants['+vi+'][id]'" :value="variant.id || ''">
                            <label class="pf-label">SKU</label>
                            <input class="pf-input" type="text" :name="'variants['+vi+'][sku]'" x-model="variant.sku" placeholder="AUTO-SKU">
                        </div>
                        <div>
                            <label class="pf-label">Price <span style="color:#ef4444">*</span></label>
                            <div class="pf-prefix-wrap"><span class="pf-prefix">BDT </span>
                                <input class="pf-input" type="number" :name="'variants['+vi+'][price]'" x-model="variant.price" step="0.01" min="0" required>
                            </div>
                        </div>
                        <div>
                            <label class="pf-label">Compare Price</label>
                            <div class="pf-prefix-wrap"><span class="pf-prefix">BDT </span>
                                <input class="pf-input" type="number" :name="'variants['+vi+'][compare_price]'" x-model="variant.compare_price" step="0.01" min="0">
                            </div>
                        </div>
                        <div>
                            <label class="pf-label">Stock</label>
                            <input class="pf-input" type="number" :name="'variants['+vi+'][quantity]'" x-model="variant.quantity" min="0">
                        </div>
                        <div style="display:flex;align-items:center;gap:10px;margin-top:20px;">
                            <label class="pf-switch" style="margin-top:0;">
                                <input type="checkbox" :name="'variants['+vi+'][is_active]'" value="1" x-model="variant.is_active">
                                <span class="pf-slider"></span>
                            </label>
                            <span class="pf-label" style="margin:0;">Active</span>
                        </div>
                    </div>
                </div>
            </template>

            <button type="button" class="pf-btn-add" style="width:100%;justify-content:center;" @click="addVariant()">
                + Add Another Variant
            </button>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════════════
         SETTINGS
    ══════════════════════════════════════════════════════════════ --}}
    <div class="pf-card">
        <div class="pf-legend"> Settings</div>
        <div style="display:flex;flex-direction:column;gap:10px;">
            @foreach ([
                ['is_active',     'Active',        'Product is visible to customers',          true],
                ['is_featured',   'Featured',      'Show in featured sections',                false],
                ['is_new',        'New Arrival',   'Show "New" badge on product',              true],
                ['is_trending',   'Trending',      'Show in trending sections',                false],
                ['free_shipping', 'Free Shipping', 'This product ships for free',             false],
            ] as [$name, $label, $sub, $default])
                <div class="pf-toggle-row">
                    <div>
                        <div class="pf-toggle-label">{{ $label }}</div>
                        <div class="pf-toggle-sub">{{ $sub }}</div>
                    </div>
                    <label class="pf-switch">
                        <input type="checkbox" name="{{ $name }}" value="1" {{ $checked($name, $default) ? 'checked' : '' }}>
                        <span class="pf-slider"></span>
                    </label>
                </div>
            @endforeach
        </div>
    </div>

</div>{{-- end .pf --}}

<script>
    function productForm() {
        return {
            slug: '{{ $v('slug') }}',
            autoSlug: {{ $isEdit ? 'false' : 'true' }},
            hasVariants: {{ $checked('has_variants') ? 'true' : 'false' }},
            tags: @json($isEdit ? ($record->tags ?? []) : (old('tags') ?? [])),
            variants: @json($isEdit ? $record->variants->map(fn($v) => [
            'id'               => $v->id,
            'sku'              => $v->sku,
            'price'            => $v->price,
            'compare_price'    => $v->compare_price,
            'quantity'         => $v->quantity,
            'is_active'        => $v->is_active,
            'attribute_values' => $v->attribute_values ?? [],
        ]) : []),

            priceCalc: { price: 0, cost: 0, profit: '0.00', margin: '0.0', discount: '0.0' },

            init() {
                // Sync richtext → hidden inputs
                const syncDesc = () => {
                    this.$refs.descInput.value   = document.getElementById('desc-editor').innerHTML;
                    this.$refs.descBnInput.value = document.getElementById('desc-bn-editor').innerHTML;
                };
                document.getElementById('desc-editor').addEventListener('input', syncDesc);
                document.getElementById('desc-bn-editor').addEventListener('input', syncDesc);

                // Price calculator
                const calcPrices = () => {
                    const price    = parseFloat(document.querySelector('[name=price]')?.value) || 0;
                    const cost     = parseFloat(document.querySelector('[name=cost_price]')?.value) || 0;
                    const compare  = parseFloat(document.querySelector('[name=compare_price]')?.value) || 0;
                    const profit   = cost > 0 ? (price - cost).toFixed(2) : '—';
                    const margin   = (cost > 0 && price > 0) ? (((price - cost) / price) * 100).toFixed(1) : '—';
                    const discount = (compare > 0 && price > 0) ? (((compare - price) / compare) * 100).toFixed(1) : '—';
                    this.priceCalc = { price, profit, margin, discount };
                };
                document.querySelector('[name=price]')?.addEventListener('input', calcPrices);
                document.querySelector('[name=cost_price]')?.addEventListener('input', calcPrices);
                document.querySelector('[name=compare_price]')?.addEventListener('input', calcPrices);
                calcPrices();
            },

            slugify(str) {
                return str.toLowerCase().replace(/[^\w\s-]/g,'').trim().replace(/[\s_-]+/g,'-');
            },

            addTag(val) {
                val = val.trim().replace(/,$/,'').trim();
                if (val && !this.tags.includes(val)) this.tags.push(val);
            },

            addVariant() {
                this.variants.push({ id: null, sku: '', price: '', compare_price: '', quantity: 0, is_active: true, attribute_values: [{attribute:'',value:''}] });
            },
        };
    }

    function previewImages(input) {
        const container = document.getElementById('new-img-preview');
        Array.from(input.files).slice(0, 8).forEach(file => {
            const reader = new FileReader();
            reader.onload = e => {
                const div = document.createElement('div');
                div.className = 'pf-img-thumb';
                div.innerHTML = `<img src="${e.target.result}"><button type="button" class="pf-img-thumb-del" onclick="this.parentNode.remove()">×</button>`;
                container.appendChild(div);
            };
            reader.readAsDataURL(file);
        });
    }

    function handleDrop(e) {
        e.preventDefault();
        document.getElementById('drop-zone').style.borderColor = '';
        const input = document.getElementById('img-upload');
        const dt = new DataTransfer();
        Array.from(e.dataTransfer.files).filter(f => f.type.startsWith('image/')).forEach(f => dt.items.add(f));
        input.files = dt.files;
        previewImages(input);
    }
</script>
