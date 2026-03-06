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
        // Soal & opsi jawaban: teks kaya + tabel + gambar + sub/sup (rumus)
        'question' => [
            'HTML.Doctype' => 'XHTML 1.0 Transitional',
            'HTML.Allowed' => 'p,br,strong,b,em,i,u,s,sub,sup,span[style|class|data-math],ul,ol,li,a[href|title],img[src|alt|width|height|style],table,thead,tbody,tfoot,tr,th,td[colspan|rowspan],div[style|class],h2,h3,h4',
            'CSS.AllowedProperties' => 'font-size,font-weight,font-style,font-family,text-decoration,text-align,color,background-color,width,height,max-width,padding,margin,border,border-collapse',
            'URI.AllowedSchemes' => ['http' => true, 'https' => true, 'data' => false],
            'AutoFormat.AutoParagraph' => true,
            'AutoFormat.RemoveEmpty' => true,
        ],
    ],
];
