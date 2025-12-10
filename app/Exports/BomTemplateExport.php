<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

use DB;

class BomTemplateExport implements WithMultipleSheets
{
    public function sheets(): array
    {
        return [
            new class implements \Maatwebsite\Excel\Concerns\FromArray, \Maatwebsite\Excel\Concerns\WithTitle {
                public function array(): array
                {
                    return [
                        [   
                            "customer",
                            "article_code_fg",
                            "article_code_rm",
                            "article_code",
                            "note",
                            "part_no",
                            "qty",
                            "uom",
                            "uom_con",
                            "article_type",
                            "urutan",
                            "pos",
                            "tone"
                        ],
                        [   
                            "IPI00001CUST",
                            "FGIPI0015",
                            "RMPIPI02",
                            "CM10000226",
                            "76085-YY050-070",
                            "",
                            "0.09260",
                            "KG",
                            "KG",
                            "CM1",
                            "1",
                            "bc",
                            "t1"
                        ],
                        [   
                            "IPI00001CUST",
                            "FGIPI0015",
                            "RMPIPI02",
                            "CM10000047",
                            "76085-YY050-070",
                            "",
                            "0.07410",
                            "KG",
                            "LTH1",
                            "CM1",
                            "2",
                            "bc",
                            "t1"
                        ]
                    ];
                }

                public function title(): string
                {
                    return 'Header';
                }
            },
            
            new class implements \Maatwebsite\Excel\Concerns\FromArray, \Maatwebsite\Excel\Concerns\WithTitle {
                public function array(): array
                {
                    return [
                        [
                            "article_code_fg",
                            "spray_booth",
                            "tone",
                            "tack",
                            "pass_rate",
                            "pass_thru",
                            "cycle_time",
                            "urutan"
                        ],
                        [
                            "FGIPI0015",
                            "sb1",
                            "t1",
                            "2",
                            "1.1",
                            "27",
                            "5.8",
                            "1"
                        ],
                        [
                            "FGIPI0015",
                            "sb1",
                            "t2",
                            "2",
                            "1.1",
                            "27",
                            "5.8",
                            "2"
                        ],
                    ];
                }

                public function title(): string
                {
                    return 'SprayBooth';
                }
            },
            
            new class implements \Maatwebsite\Excel\Concerns\FromArray, \Maatwebsite\Excel\Concerns\WithTitle {
                public function array(): array
                {
                    return [
                        ["pos_code","pos_name"],
                        ["pc","Primer Coat"],
                        ["bc","Base Coat"],
                        ["mbc","Mica Base Coat"],
                        ["cc","Clear Coat"],
                        ["pr","Preparation"],
                        ["str","Stripping"],
                        ["snd","Sanding"],
                        ["ass","Assembling"],
                        ["pac","Packing"],
                        ["asy","Assy"]
                    ];
                }

                public function title(): string
                {
                    return 'master_pos';
                }
            },

            new class implements \Maatwebsite\Excel\Concerns\FromArray, \Maatwebsite\Excel\Concerns\WithTitle {
                public function array(): array
                {
                    return [
                        ["tone_code","tome_name"],
                        ["t1","Tone 1"],
                        ["t2","Tone 2"],
                        ["t3","Tone 3"],
                        ["t4","Tone 4"]
                    ];
                }

                public function title(): string
                {
                    return 'master_tone';
                }
            },

            new class implements \Maatwebsite\Excel\Concerns\FromArray, \Maatwebsite\Excel\Concerns\WithTitle {
                public function array(): array
                {
                    return [
                        ["sb1","Spraybooth 1"],
                        ["sb1a","Spraybooth 1 A"],
                        ["sb1b","Spraybooth 1 B"],
                        ["sb1c","Spraybooth 1 C"],
                        ["sb2","Spraybooth 2"],
                        ["sb2a","Spraybooth 2 A"],
                        ["sb2b","Spraybooth 2 B"],
                        ["sb2c","Spraybooth 2 C"],
                        ["sb3","Spraybooth 3"],
                        ["sb3a","Spraybooth 3 A"],
                        ["sb3b","Spraybooth 3 B"],
                        ["sb3c","Spraybooth 3 C"],
                        ["sb4","Spraybooth 4"],
                        ["sb4a","Spraybooth 4 A"],
                        ["sb4b","Spraybooth 4 B"],
                        ["sb4c","Spraybooth 4 C"],
                        ["sbtoto","Toto"],
                    ];
                }

                public function title(): string
                {
                    return 'master_spray_booth';
                }
            }
        ];
    }

    // public function collection()
    // {
    //     return DB::table('bom_upload_tmp')
    //     ->where('file_name','okihartantokeren')
    //     ->get();
    // }

    // public function headings(): array
    // {
    //     return [
    //         "customer",
    //         "article_code_fg",
    //         "article_code_rm",
    //         "article_code",
    //         "group_of_material",
    //         "note",
    //         "part_no",
    //         "qty",
    //         "uom",
    //         "uom_con",
    //         "cost_price",
    //         "article_type",
    //         "status",
    //         "urutan",
    //         "pos",
    //         "tone"
    //     ];
    
}