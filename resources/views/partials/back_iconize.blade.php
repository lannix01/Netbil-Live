<style>
    .ui-back-icon-link{
        position:relative;
        display:inline-flex !important;
        align-items:center;
        justify-content:center;
        width:38px !important;
        min-width:38px;
        height:38px !important;
        min-height:38px;
        padding:0 !important;
        gap:0 !important;
        border-radius:999px !important;
        line-height:1;
        font-size:0 !important;
        order:-100;
        transition:transform .12s ease;
    }
    .ui-back-icon-link:hover{
        transform:translateX(-1px);
    }
    .ui-back-icon-link svg{
        width:18px;
        height:18px;
        display:block;
        pointer-events:none;
    }
    .ui-back-icon-sr{
        position:absolute;
        width:1px;
        height:1px;
        padding:0;
        margin:-1px;
        overflow:hidden;
        clip:rect(0, 0, 0, 0);
        white-space:nowrap;
        border:0;
    }
</style>

<script>
(function(){
    const PROCESSED_ATTR = 'data-back-iconized';

    function normalizedText(el){
        return String(el && el.textContent ? el.textContent : '')
            .replace(/\s+/g, ' ')
            .trim();
    }

    function shouldConvert(el){
        if (!(el instanceof HTMLElement)) return false;
        if (!el.matches('a,button')) return false;
        if (el.getAttribute(PROCESSED_ATTR) === '1') return false;
        if (el.closest('[data-no-back-icon]')) return false;
        if (el.querySelector('input,select,textarea')) return false;

        const label = normalizedText(el);
        return /^back(\b|$)/i.test(label);
    }

    function buildIcon(){
        const ns = 'http://www.w3.org/2000/svg';
        const svg = document.createElementNS(ns, 'svg');
        svg.setAttribute('viewBox', '0 0 24 24');
        svg.setAttribute('fill', 'none');
        svg.setAttribute('aria-hidden', 'true');

        const path = document.createElementNS(ns, 'path');
        path.setAttribute('d', 'M15.5 19 8.5 12l7-7');
        path.setAttribute('stroke', 'currentColor');
        path.setAttribute('stroke-width', '2.2');
        path.setAttribute('stroke-linecap', 'round');
        path.setAttribute('stroke-linejoin', 'round');
        svg.appendChild(path);

        return svg;
    }

    function convert(el){
        if (!shouldConvert(el)) return;

        const label = el.getAttribute('aria-label') || normalizedText(el) || 'Back';
        el.setAttribute(PROCESSED_ATTR, '1');
        el.setAttribute('aria-label', label);
        el.setAttribute('title', label);
        el.classList.add('ui-back-icon-link');

        while (el.firstChild) {
            el.removeChild(el.firstChild);
        }

        const icon = buildIcon();
        const sr = document.createElement('span');
        sr.className = 'ui-back-icon-sr';
        sr.textContent = label;

        el.appendChild(icon);
        el.appendChild(sr);
    }

    function scan(root){
        if (!root || typeof root.querySelectorAll !== 'function') return;

        if (root instanceof HTMLElement && shouldConvert(root)) {
            convert(root);
        }

        root.querySelectorAll('a,button').forEach(convert);
    }

    document.addEventListener('DOMContentLoaded', function(){
        scan(document);

        const observer = new MutationObserver(function(mutations){
            mutations.forEach(function(mutation){
                mutation.addedNodes.forEach(function(node){
                    if (!(node instanceof HTMLElement)) return;
                    scan(node);
                });
            });
        });

        observer.observe(document.body, { childList:true, subtree:true });
    });
})();
</script>
