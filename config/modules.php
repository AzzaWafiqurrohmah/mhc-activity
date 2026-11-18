<?php

/*
 | Daftar modul report.
 | Kunci modul memakai underscore (bukan titik) supaya aman dipanggil lewat config().
 | 'divisions' berisi KODE divisi (kolom divisions.code) yang boleh memakai modul ini.
 */
return [

    'cs_do' => [
        'label'     => 'Dokumen Pengiriman (DO)',
        'route'     => 'staff.cs.do.index',
        'pattern'   => 'staff.cs.do.*',
        'divisions' => ['cs'],
    ],

    'cs_wa' => [
        'label'     => 'Sales Online (WA)',
        'route'     => 'staff.cs.wa.index',
        'pattern'   => 'staff.cs.wa.*',
        'divisions' => ['cs'],
    ],

    'cs_member' => [
        'label'     => 'Member',
        'route'     => 'staff.cs.member.index',
        'pattern'   => 'staff.cs.member.*',
        'divisions' => ['cs'],
    ],

    'cs_claim' => [
        'label'     => 'Komplain, Retur & Garansi',
        'route'     => 'staff.cs.claim.index',
        'pattern'   => 'staff.cs.claim.*',
        'divisions' => ['cs'],
    ],

    'cs_reward' => [
        'label'     => 'Hadiah Pembelanjaan',
        'route'     => 'staff.cs.reward.index',
        'pattern'   => 'staff.cs.reward.*',
        'divisions' => ['cs'],
    ],
];