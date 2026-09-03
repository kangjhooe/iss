@php
    use App\Support\PrintDocumentFooter;

    $resolvedBy = $printed_by ?? ($printedBy ?? null);
    $resolvedAt = $printed_at ?? ($printedAt ?? null);
    $footerLine = PrintDocumentFooter::line($resolvedBy, $resolvedAt);

    if (!empty($footer_suffix)) {
        $footerLine .= ' '.$footer_suffix;
    }

    $footerClasses = trim(($footer_class ?? 'footer').' print-document-footer');
    $footerTag = $footer_tag ?? 'div';
@endphp
@if($footerTag === 'p')
<p class="{{ $footerClasses }}">{{ $footerLine }}</p>
@else
<div class="{{ $footerClasses }}">{{ $footerLine }}</div>
@endif
