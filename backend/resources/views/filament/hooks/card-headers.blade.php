{{-- Coloured card headers for every Filament section / widget card (white text on a gradient). --}}
<style>
    .fi-section:not(.fi-aside) > .fi-section-header {
        --hdr-a: #047857; --hdr-b: #059669;
        background: linear-gradient(135deg, var(--hdr-a), var(--hdr-b));
        border-radius: 0.75rem 0.75rem 0 0;
    }
    .fi-section:not(.fi-aside).fi-collapsed > .fi-section-header { border-radius: 0.75rem; }
    .fi-section:not(.fi-aside) > .fi-section-header .fi-section-header-heading,
    .fi-section:not(.fi-aside) > .fi-section-header .fi-section-header-icon,
    .fi-section:not(.fi-aside) > .fi-section-header button,
    .fi-section:not(.fi-aside) > .fi-section-header svg { color: #fff !important; }
    .fi-section:not(.fi-aside) > .fi-section-header .fi-section-header-heading { font-weight: 700; text-shadow: 0 1px 1px rgba(0,0,0,.18); }
    .fi-section:not(.fi-aside) > .fi-section-header .fi-section-header-description { color: rgba(255,255,255,.88) !important; }
    .fi-section:not(.fi-aside) > .fi-section-header .fi-section-header-icon { opacity: .95; }
    .fi-section[data-hdr="0"] > .fi-section-header { --hdr-a:#047857; --hdr-b:#059669; }
    .fi-section[data-hdr="1"] > .fi-section-header { --hdr-a:#1d4ed8; --hdr-b:#2563eb; }
    .fi-section[data-hdr="2"] > .fi-section-header { --hdr-a:#6d28d9; --hdr-b:#7c3aed; }
    .fi-section[data-hdr="3"] > .fi-section-header { --hdr-a:#be123c; --hdr-b:#e11d48; }
    .fi-section[data-hdr="4"] > .fi-section-header { --hdr-a:#b45309; --hdr-b:#d97706; }
    .fi-section[data-hdr="5"] > .fi-section-header { --hdr-a:#0e7490; --hdr-b:#0891b2; }
    .fi-section[data-hdr="6"] > .fi-section-header { --hdr-a:#4338ca; --hdr-b:#4f46e5; }
    .fi-section[data-hdr="7"] > .fi-section-header { --hdr-a:#a21caf; --hdr-b:#c026d3; }
    .fi-section[data-hdr="8"] > .fi-section-header { --hdr-a:#c2410c; --hdr-b:#ea580c; }
    .fi-section[data-hdr="9"] > .fi-section-header { --hdr-a:#0f766e; --hdr-b:#0d9488; }
</style>
<script>
    (function () {
        // Give each card a different header colour, in page order (stable for the same page layout).
        function paint() {
            var i = 0;
            document.querySelectorAll('.fi-section:not(.fi-aside)').forEach(function (el) {
                if (!el.querySelector(':scope > .fi-section-header')) return;
                var v = String(i++ % 10);
                if (el.getAttribute('data-hdr') !== v) el.setAttribute('data-hdr', v);
            });
        }
        var t;
        function schedule() { clearTimeout(t); t = setTimeout(paint, 30); }
        document.addEventListener('DOMContentLoaded', function () {
            paint();
            new MutationObserver(schedule).observe(document.body, { childList: true, subtree: true });
        });
        document.addEventListener('livewire:navigated', schedule);
    })();
</script>
