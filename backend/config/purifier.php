<?php

return [
    'encoding' => 'UTF-8',
    'finalize' => true,
    'ignoreNonStrings' => false,
    'cachePath' => storage_path('app/purifier'),
    'cacheFileMode' => 0755,
    'settings' => [
        'default' => [
            'HTML.Doctype' => 'HTML 4.01 Transitional',
            'HTML.Allowed' => 'div,b,strong,i,em,u,a[href|title],ul,ol,li,p[style],br,span[style],img[width|height|alt|src]',
            'CSS.AllowedProperties' => 'font,font-size,font-weight,font-style,font-family,text-decoration,padding-left,color,background-color,text-align',
            'AutoFormat.AutoParagraph' => true,
            'AutoFormat.RemoveEmpty' => true,
        ],
        // Soal & opsi jawaban: teks kaya + tabel + gambar + sub/sup + rumus (data-math) + arah Arab
        'question' => [
            'HTML.Doctype' => 'XHTML 1.0 Transitional',
            'HTML.Allowed' => 'p[style|class|dir],br,strong,b,em,i,u,s,sub,sup,span[style|class|dir|data-math|data-display],ul[class|dir],ol[class|dir],li[class|dir|style|data-list],a[href|title],img[src|alt|width|height|style],table[class],thead,tbody,tfoot,tr,th[colspan|rowspan|class|dir|style],td[colspan|rowspan|class|dir|style],div[style|class|dir],h2[class|dir],h3[class|dir],h4[class|dir],figure[class],figcaption,audio[src|controls|preload|style],video[src|controls|preload|width|height|style],source[src|type],iframe[src|width|height|frameborder|allowfullscreen|allow|title|style|class]',
            'CSS.AllowedProperties' => 'font-size,font-weight,font-style,font-family,text-decoration,text-align,color,background-color,width,height,max-width,padding,margin,border,border-collapse,direction,unicode-bidi',
            'URI.AllowedSchemes' => ['http' => true, 'https' => true, 'data' => false],
            'HTML.SafeIframe' => true,
            'URI.SafeIframeRegexp' => '%^(https?:)?//(www\.youtube(?:-nocookie)?\.com/embed/|player\.vimeo\.com/video/)%',
            'AutoFormat.AutoParagraph' => true,
            'AutoFormat.RemoveEmpty' => true,
        ],
    ],
];
