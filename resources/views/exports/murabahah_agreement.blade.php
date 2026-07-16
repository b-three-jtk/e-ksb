<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Perjanjian Murabahah</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body { font-family: 'Times New Roman', Times, serif; font-size: 14px; line-height: 1.5; background: white; padding: 20px; }
        .table-header { border-collapse: collapse; width: 100%; border: 1px #000 solid; margin-bottom: 20px; }
        .table-header td { padding: 3px 0; border: 1px #000 solid; }
        .content { margin-top: 10px; text-align: justify; }
        .title { text-align: center; font-weight: bold; }
        .bismillah { text-align: center; font-size: 24px; font-weight: bold; margin-bottom: 5px; font-family: 'Amiri', 'Traditional Arabic', serif;}
        .bismillah-text { text-align: center; margin-bottom: 20px; font-style: italic; }
        .doc-title { text-align: center; font-weight: bold; margin-bottom: 20px; text-transform: uppercase; }
        
        table.identitas { width: 100%; margin-bottom: 10px; }
        table.identitas td { vertical-align: top; }
        
        .pasal-title { text-align: center; font-weight: bold; margin-top: 20px; margin-bottom: 10px; }
        
        table.rincian { width: 100%; margin-bottom: 10px; margin-left: 20px;}
        table.rincian td { vertical-align: top; }
        
        ol, ul { padding-left: 20px; margin-bottom: 10px; }
        li { margin-bottom: 5px; }
    </style>
</head>
<body>
    <table class="table-header">
        <tr>
            <td rowspan="2" style="width: 15%; text-align: center; border: 1px #000 solid;">
                <img style="width: 70px;" src="{{ $src }}" alt="Logo KSB">
            </td>
            <td class="title" colspan="2" style="border-bottom: 1px #000 solid;font-size:14px; padding: 5px;">
                KOPERASI SYARIAH BERKAH<br>
                <span style="font-weight: normal; font-size: 11px;">MT. Mutiara Hikmah<br>DEWAN KELUARGA MASJID AL-HIKMAH<br>Jl. Komp. Puri Cipageran Indah 2 RW 21</span>
            </td>
        </tr>
        <tr>
            <td style="font-weight: bold;text-align: center;font-size:12px;border: 1px #000 solid; width: 50%;">
                PERJANJIAN MURABAHAH
            </td>
            <td style="font-weight: bold;text-align:center;font-size:12px; border: 1px #000 solid;">
                No. Dokumen: <span style="font-weight:normal;">{{ $noDokumen }}</span>
            </td>
        </tr>
    </table>
    <div class="content">
        <div class="bismillah-text"><strong>Bismillaahirrahmaanirrahiim</strong><br>"Dengan menyebut Nama Allah Yang Maha Pengasih lagi Maha Penyayang"</div>

        <div class="doc-title">
            PERJANJIAN PEMBIAYAAN MURABAHAH<br>
            ANTARA<br>
            KOPERASI SYARIAH BERKAH<br>
            DAN<br>
            {{ $pembiayaan->anggota->user->nama }}<br>
            Nomor: {{ $noDokumen }}
        </div>

        <p style="margin-bottom: 10px;">Yang bertanda tangan dibawah ini:</p>

        <table class="identitas">
            <tr>
                <td style="width: 3%;">I.</td>
                <td colspan="3">Koperasi Syariah Berkah dalam hal ini melalui,</td>
            </tr>
            <tr>
                <td></td>
                <td style="width: 30%;">Diwakili oleh</td>
                <td style="width: 2%;">:</td>
                <td>{{ $ketuaKoperasi }}</td>
            </tr>
            <tr>
                <td></td>
                <td>Dalam Kapasitasnya selaku</td>
                <td>:</td>
                <td>Ketua Koperasi</td>
            </tr>
            <tr>
                <td></td>
                <td colspan="3" style="text-align: justify;">Berdasarkan Perjanjian Layanan Pembiayaan Berbasis Teknologi Dengan Prinsip Syariah No. ..... tanggal dalam hal ini bertindak selaku wakil dari <strong>PEMBERI PEMBIAYAAN</strong>, selanjutnya disebut <strong>PENYELENGGARA</strong>;</td>
            </tr>
        </table>

        <table class="identitas" style="margin-top: 15px;">
            <tr>
                <td style="width: 3%;">II.</td>
                <td style="width: 30%;">Nama</td>
                <td style="width: 2%;">:</td>
                <td>{{ $pembiayaan->anggota->user->nama }}</td>
            </tr>
            <tr>
                <td></td>
                <td>Nomor KTP</td>
                <td>:</td>
                <td>{{ $pembiayaan->anggota->user->nik }}</td>
            </tr>
            <tr>
                <td></td>
                <td colspan="3" style="text-align: justify;">Dalam hal ini bertindak untuk diri sendiri, selanjutnya disebut <strong>PENERIMA PEMBIAYAAN</strong>.</td>
            </tr>
        </table>

        <p style="margin-top: 15px;">Bahwa <strong>PENERIMA PEMBIAYAAN</strong> telah mengajukan permohonan fasilitas pembiayaan kepada <strong>PENYELENGGARA</strong> untuk membeli Barang (sebagaimana didefinisikan dalam Perjanjian) dan selanjutnya <strong>PENYELENGGARA</strong> menyetujui untuk menyediakan fasilitas pembiayaan sesuai dengan ketentuan dan syarat-syarat sebagaimana dinyatakan dalam Perjanjian.</p>

        <p style="margin-top: 15px;">Dengan ini kedua belah pihak telah sepakat untuk mengadakan Perjanjian Pembiayaan dengan prinsip Murabahah (selanjutnya disebut "Akad") berdasarkan ketentuan dan syarat-syarat sebagai berikut:</p>

        <div class="pasal-title">
            PASAL 1<br>
            KETENTUAN POKOK AKAD
        </div>

        <p>Ketentuan-ketentuan pokok Akad ini meliputi sebagai berikut:</p>
        <table class="rincian">
            <tr><td style="width: 3%;">a.</td><td style="width: 35%;">Harga Perolehan</td><td style="width: 2%;">:</td><td>Rp {{ number_format($hargaBeli, 0, ',', '.') }}</td></tr>
            <tr><td>b.</td><td>Margin Keuntungan</td><td>:</td><td>Rp {{ number_format($margin, 0, ',', '.') }}</td></tr>
            <tr><td>c.</td><td>Uang Muka</td><td>:</td><td>Rp {{ number_format($uangMuka, 0, ',', '.') }}</td></tr>
            <tr><td>d.</td><td>Harga Jual Murabahah</td><td>:</td><td>Rp {{ number_format($hargaJual, 0, ',', '.') }}</td></tr>
            <tr><td>e.</td><td>Kegunaan/Jenis Pembiayaan</td><td>:</td><td>Pembiayaan Kepemilikan {{ $pembiayaan->objekPembiayaan->jenisBarang->nama_jenis_barang ?? 'Barang' }}</td></tr>
            <tr><td>f.</td><td>Jangka Waktu Pembiayaan</td><td>:</td><td>{{ $tenor }} {{ ucfirst($satuanTenor) }}</td></tr>
            <tr><td>g.</td><td>Jatuh Tempo Pembiayaan</td><td>:</td><td>{{ $tglLunas->translatedFormat('d F Y') }}</td></tr>
            <tr><td>h.</td><td>Angsuran per {{ strtolower($satuanTenor) === 'minggu' ? 'minggu' : 'bulan' }}</td><td>:</td><td>Rp {{ number_format($angsuran, 0, ',', '.') }} per {{ strtolower($satuanTenor) === 'minggu' ? 'minggu' : 'bulan' }}</td></tr>
            <tr><td>i.</td><td>Jatuh Tempo Pembayaran Angsuran</td><td>:</td><td>Setiap {{ strtolower($satuanTenor) === 'minggu' ? 'hari' : 'tanggal' }} {{ $tanggalJatuhTempo }} per {{ strtolower($satuanTenor) === 'minggu' ? 'minggu' : 'bulan' }}</td></tr>
            @if ($pembiayaan->jaminan)
            <tr><td>j.</td><td>Jenis Jaminan</td><td>:</td><td>{{ $pembiayaan->jaminan->jenis_jaminan ?? '........................' }}</td></tr>
            <tr><td>k.</td><td>Nama Pemilik Aset</td><td>:</td><td>{{ $pembiayaan->jaminan->nama_pemilik ?? '........................' }}</td></tr>
            @endif
        </table>

        <div class="pasal-title">
            PASAL 2<br>
            DEFINISI
        </div>
        <p>Dalam Akad ini yang dimaksud dengan:</p>
        <ol>
            <li><strong>Akad</strong> adalah perjanjian tertulis tentang fasilitas Pembiayaan Murabahah yang dibuat oleh <strong>PENYELENGGARA</strong> dan <strong>PENERIMA PEMBIAYAAN</strong> memuat ketentuan-ketentuan dan syarat-syarat yang disepakati, berikut perubahan-perubahan dan tambahan-tambahannya (addendum), sesuai dengan ketentuan dan perundang-undangan.</li>
            <li><strong>PENYELENGGARA</strong> adalah penyedia layanan pembiayaan berbasis teknologi dengan prinsip syariah yang menghimpun dana dari pemberi pembiayaan dan yang menyediakan fasilitas pembiayaan kepada <strong>PENERIMA PEMBIAYAAN</strong> atas pembelian barang oleh <strong>PENERIMA PEMBIAYAAN</strong> dari Pemasok.</li>
            <li><strong>Barang</strong> adalah berupa {{ $pembiayaan->objekPembiayaan->nama_barang ?? '.........................................' }} yang dibiayai oleh <strong>PENYELENGGARA</strong> untuk kepentingan <strong>PENERIMA PEMBIAYAAN</strong>.</li>
            <li><strong>PENERIMA PEMBIAYAAN</strong> adalah penerima fasilitas pembiayaan yang berkewajiban membeli Barang sesuai yang disepakati oleh <strong>PENERIMA PEMBIAYAAN</strong> kepada <strong>PENYELENGGARA</strong>.</li>
            <li><strong>Pembiayaan Murabahah</strong> adalah penyediaan uang atau tagihan yang dipersamakan dengan itu, berdasarkan persetujuan atau kesepakatan antara <strong>PENYELENGGARA</strong> dengan <strong>PENERIMA PEMBIAYAAN</strong> untuk pembelian barang yang mewajibkan <strong>PENERIMA PEMBIAYAAN</strong> untuk mengembalikan uang atau tagihan tersebut setelah jangka waktu tertentu dengan margin keuntungan.</li>
            <li><strong>Harga Beli</strong> adalah sejumlah uang yang harus dibayar oleh <strong>PENYELENGGARA</strong> kepada Pemasok untuk membiayai pembelian barang atas permintaan <strong>PENERIMA PEMBIAYAAN</strong> yang disetujui oleh <strong>PENYELENGGARA</strong> ditambah (termasuk) biaya-biaya langsung yang dikeluarkan oleh <strong>PENYELENGGARA</strong> untuk membiayai Barang yang dibeli <strong>PENERIMA PEMBIAYAAN</strong> tersebut.</li>
            <li><strong>Harga Jual</strong> adalah harga beli ditambah margin keuntungan <strong>PENYELENGGARA</strong> yang ditetapkan oleh <strong>PENYELENGGARA</strong> dan disetujui/disepakati oleh <strong>PENERIMA PEMBIAYAAN</strong> yang merupakan jumlah Pembiayaan.</li>
            <li><strong>Margin Keuntungan</strong> adalah jumlah uang yang wajib dibayar <strong>PENERIMA PEMBIAYAAN</strong> kepada <strong>PENYELENGGARA</strong> sebagai imbalan atas Pembiayaan yang diberikan oleh <strong>PENYELENGGARA</strong>, yang merupakan selisih antara Harga Jual dan Harga Beli.</li>
            <li><strong>Uang Muka</strong> adalah sejumlah uang yang besarnya ditetapkan oleh <strong>PENYELENGGARA</strong> dan disetujui oleh <strong>PENERIMA PEMBIAYAAN</strong> yang harus dibayarkan terlebih dahulu oleh <strong>PENERIMA PEMBIAYAAN</strong> kepada <strong>PENYELENGGARA</strong> sebagai salah satu syarat yang harus dipenuhi <strong>PENYELENGGARA</strong> untuk memperoleh Pembiayaan Murabahah dari <strong>PENYELENGGARA</strong>.</li>
            <li><strong>Piutang Murabahah</strong> adalah hak tagih <strong>PENYELENGGARA</strong> kepada <strong>PENERIMA PEMBIAYAAN</strong> yang timbul karena <strong>PENERIMA PEMBIAYAAN</strong> telah menerima fasilitas pembiayaan dari <strong>PENERIMA PEMBIAYAAN</strong> dan besarnya adalah sama dengan Harga Jual.</li>
            <li><strong>Hutang Murabahah</strong> adalah sejumlah kewajiban keuangan <strong>PENERIMA PEMBIAYAAN</strong> kepada <strong>PENYELENGGARA</strong> yang timbul dari realisasi Pembiayaan berdasarkan Akad ini, maksimal sebesar harga jual Barang.</li>
            <li><strong>Angsuran</strong> adalah sejumlah uang untuk pembayaran Jumlah Harga Jual yang wajib dibayar secara bulanan oleh <strong>PENERIMA PEMBIAYAAN</strong> kepada <strong>PENYELENGGARA</strong> sebagaimana ditentukan Akad ini.</li>
            <li><strong>Jatuh Tempo Pembayaran Angsuran</strong> adalah tanggal <strong>PENERIMA PEMBIAYAAN</strong> berkewajiban membayar angsuran setiap bulan.</li>
            <li><strong>Tunggakan</strong> adalah suatu Hutang Murabahah yang telah jatuh tempo, tetapi belum dibayar oleh <strong>PENERIMA PEMBIAYAAN</strong>.</li>
            <li><strong>Pemasok</strong> adalah pihak ketiga yang menyediakan Barang yang dibutuhkan oleh <strong>PENERIMA PEMBIAYAAN</strong> untuk dan atas nama <strong>PENYELENGGARA</strong>.</li>
            @if ($pembiayaan->jaminan)
            <li><strong>Jaminan</strong> adalah jaminan yang bersifat materiil maupun immaterial untuk mendukung keyakinan <strong>PENYELENGGARA</strong> atas kemampuan dan kesanggupan <strong>PENERIMA PEMBIAYAAN</strong> untuk melunasi Hutangnya sesuai Akad.</li>
            <li><strong>Dokumen Jaminan</strong> adalah akta-akta, surat-surat bukti kepemilikan, dan surat lainnya yang merupakan bukti hak atas barang jaminan berikut surat-surat lain yang merupakan satu kesatuan dan bagian tidak terpisah dari barang jaminan guna menjamin pemenuhan kewajiban <strong>PENERIMA PEMBIAYAAN</strong> kepada <strong>PENYELENGGARA</strong> berdasarkan Akad ini.</li>
            @endif
            <li><strong>Hari Kerja</strong> adalah Hari Kerja Otoritas Jasa Keuangan.</li>
        </ol>

        <div class="pasal-title">
            PASAL 3<br>
            PELAKSANAAN PRINSIP MURABAHAH
        </div>
        <p>Pelaksanaan prinsip Murabahah yang berlangsung antara <strong>PENYELENGGARA</strong> dengan <strong>PENERIMA PEMBIAYAAN</strong> sebagai Penerima Fasilitas Pembiayaan dilaksanakan dan diatur menurut ketentuan-ketentuan dan persyaratan sebagai berikut :</p>
        <ol>
            <li><strong>PENERIMA PEMBIAYAAN</strong> membutuhkan Barang dengan spesifikasi sebagaimana terdapat pada Lampiran [2] dan meminta kepada <strong>PENYELENGGARA</strong> untuk memberikan fasilitas Pembiayaan Murabahah guna pembelian Barang.</li>
            <li><strong>PENYELENGGARA</strong> bersedia menyediakan Pembiayaan Murabahah sesuai dengan permohonan <strong>PENERIMA PEMBIAYAAN</strong>.</li>
            <li><strong>PENERIMA PEMBIAYAAN</strong> bersedia membayar Harga Jual Barang sesuai Akad ini, dan Harga Jual tidak dapat berubah selama berlakunya Akad ini.</li>
            <li><strong>PENYELENGGARA</strong> dengan Akad ini mewakilkan secara penuh kepada <strong>PENERIMA PEMBIAYAAN</strong> untuk membeli dan menerima Barang dari Pemasok, serta memberi hak melakukan pembuatan akta jual beli untuk dan atas nama <strong>PENERIMA PEMBIAYAAN</strong> sendiri langsung dengan Pemasok.</li>
            <li>Pemberian kuasa sebagaimana dimaksud dalam ayat 4 pasal ini, tidak mengakibatkan <strong>PENERIMA PEMBIAYAAN</strong> dapat membatalkan jual beli Barang serta <strong>PENERIMA PEMBIAYAAN</strong> tidak dapat menuntut <strong>PENYELENGGARA</strong> untuk memberikan ganti rugi sebagaimana dimaksud dalam pasal 1471 Kitab Undang-Undang Hukum Perdata.</li>
        </ol>

        <div class="pasal-title">
            PASAL 4<br>
            SYARAT REALISASI PEMBIAYAAN
        </div>
        <ol>
            <li><strong>PENYELENGGARA</strong> akan merealisasikan Pembiayaan berdasarkan prinsip Murabahah berdasarkan Akad ini, setelah <strong>PENERIMA PEMBIAYAAN</strong> terlebih dahulu memenuhi seluruh persyaratan sebagai berikut:
                <ol type="a">
                    <li>Menyerahkan kepada <strong>PENYELENGGARA</strong> seluruh dokumen yang disyaratkan oleh <strong>PENYELENGGARA</strong> termasuk tetapi tidak terbatas pada dokumen bukti diri <strong>PENERIMA PEMBIAYAAN</strong>{{ $pembiayaan->jaminan ? ', dokumen kepemilikan jaminan dan atau surat lainnya yang berkaitan dengan Akad ini dan pengikatan jaminan' : '' }}, yang ditentukan dalam Surat Penawaran Pembiayaan dari <strong>PENYELENGGARA</strong>.</li>
                    <li><strong>PENERIMA PEMBIAYAAN</strong> wajib membuka dan memelihara akun pada <strong>PENYELENGGARA</strong> selama <strong>PENERIMA PEMBIAYAAN</strong> mempunyai Pembiayaan Murabahah dari <strong>PENYELENGGARA</strong>.</li>
                    <li>Menandatangani Akad ini{{ $pembiayaan->jaminan ? ' dan perjanjian pengikatan jaminan yang disyaratkan oleh PENYELENGGARA' : '' }}.</li>
                    <li>Menyetorkan uang muka pembelian dan atau biaya-biaya yang disyaratkan oleh <strong>PENYELENGGARA</strong> sebagai yang tercantum dalam Surat Penawaran Pembiayaan.</li>
                </ol>
            </li>
            <li>Realisasi Pembiayaan Murabahah akan dilakukan oleh <strong>PENYELENGGARA</strong> kepada Pemasok, baik secara langsung maupun melalui <strong>PENERIMA PEMBIAYAAN</strong>.</li>
            <li>Sejak ditandatanganinya Akad ini dan telah diterimanya Barang pesanan oleh <strong>PENERIMA PEMBIAYAAN</strong>, maka risiko atas Barang tersebut sepenuhnya menjadi tanggung jawab <strong>PENERIMA PEMBIAYAAN</strong> dan dengan ini <strong>PENERIMA PEMBIAYAAN</strong> membebaskan <strong>PENYELENGGARA</strong> dari segala tuntutan dan atau ganti rugi berupa apapun atas risiko tersebut.</li>
            <li>Apabila <strong>PENYELENGGARA</strong> telah membayar kepada Pemasok termasuk pembayaran uang muka, maka <strong>PENERIMA PEMBIAYAAN</strong> tidak dapat membatalkan secara sepihak Akad ini.</li>
        </ol>

        <div class="pasal-title">
            PASAL 5<br>
            JATUH TEMPO PEMBIAYAAN
        </div>
        <p>Fasilitas pembiayaan Murabahah yang dimaksud dalam Akad ini berlangsung untuk jangka waktu {{ $tenor }} {{ $satuanTenor }} terhitung sejak tanggal Akad ini ditandatangani serta berakhir pada tanggal {{ $tglLunas->translatedFormat('d F Y') }}. Berakhirnya jatuh tempo Pembiayaan tidak dengan sendirinya menyebabkan Hutang lunas sepanjang masih terdapat sisa Hutang <strong>PENERIMA PEMBIAYAAN</strong>.</p>
        
        <br><br>
        <table class="signatures" style="width: 100%; border-collapse: collapse; margin-top: 40px; page-break-inside: avoid;">
            <tr>
                <td style="text-align: center; width: 50%; padding: 10px;">
                    <div style="margin-bottom: 80px;">PENYELENGGARA</div>
                    <div style="font-weight: bold;">({{ $ketuaKoperasi }})</div>
                    <div>Ketua Koperasi</div>
                </td>
                <td style="text-align: center; width: 50%; padding: 10px;">
                    <div style="margin-bottom: 80px;">PENERIMA PEMBIAYAAN</div>
                    <div style="font-weight: bold;">({{ $pembiayaan->anggota->user->nama }})</div>
                    <div>Anggota</div>
                </td>
            </tr>
        </table>
        
        <div style="page-break-before: always;"></div>
        
        <table class="table-header">
            <tr>
                <td rowspan="2" style="width: 15%; text-align: center; border: 1px #000 solid;">
                    <img style="width: 70px;" src="{{ $src }}" alt="Logo KSB">
                </td>
                <td class="title" colspan="2" style="border-bottom: 1px #000 solid;font-size:16px; padding: 10px;">
                    KOPERASI SYARIAH BERKAH
                </td>
            </tr>
            <tr>
                <td style="font-weight: bold;text-align: center;font-size:14px;border: 1px #000 solid; width: 50%;">
                    FORMULIR&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;PEMBIAYAAN MURABAHAH
                </td>
                <td style="font-weight: bold;text-align:center;font-size:14px; border: 1px #000 solid;">
                    No. Dokumen: <span style="font-weight:normal;">{{ $noDokumen }}</span>
                </td>
            </tr>
        </table>
        
        <div class="pasal-title">
            LAMPIRAN [1]<br>
            JADWAL PEMBAYARAN ANGSURAN
        </div>
        <table style="width: 50%; margin-bottom: 20px;">
            <tr><td>Harga Perolehan</td><td>: Rp {{ number_format($hargaBeli, 0, ',', '.') }}</td></tr>
            <tr><td>Margin Keuntungan</td><td>: Rp {{ number_format($margin, 0, ',', '.') }}</td></tr>
            <tr><td>Harga Jual</td><td>: Rp {{ number_format($hargaJual, 0, ',', '.') }}</td></tr>
            <tr><td>Uang Muka</td><td>: Rp {{ number_format($uangMuka, 0, ',', '.') }}</td></tr>
            <tr><td>Angsuran per {{ ucfirst($satuanTenor) }}</td><td>: Rp {{ number_format($angsuran, 0, ',', '.') }}</td></tr>
        </table>
        
        <table style="width: 100%; border-collapse: collapse; text-align: center;" border="1">
            <thead>
                <tr>
                    <th style="padding: 5px;">Tanggal Pembayaran</th>
                    <th style="padding: 5px;">Jumlah Pembayaran Angsuran</th>
                </tr>
            </thead>
            <tbody>
                @for ($i = 1; $i <= $tenor; $i++)

                <tr>
                    <td style="padding: 5px;">
                        {{ 
                            strtolower($satuanTenor) === 'minggu' 
                                ? \Carbon\Carbon::parse($pembiayaan->tgl_akad)->addWeeks($i)->translatedFormat('d F Y') 
                                : \Carbon\Carbon::parse($pembiayaan->tgl_akad)->addMonths($i)->translatedFormat('d F Y') 
                        }}
                    </td>
                    <td style="padding: 5px;">Rp {{ number_format($angsuran, 0, ',', '.') }}</td>
                </tr>
                @endfor
            </tbody>
        </table>

        <div style="page-break-before: always;"></div>
        
        <table class="table-header">
            <tr>
                <td rowspan="2" style="width: 15%; text-align: center; border: 1px #000 solid;">
                    <img style="width: 70px;" src="{{ $src }}" alt="Logo KSB">
                </td>
                <td class="title" colspan="2" style="border-bottom: 1px #000 solid;font-size:14px; padding: 5px;">
                    KOPERASI SYARIAH BERKAH<br>
                    <span style="font-weight: normal; font-size: 11px;">MT. Mutiara Hikmah<br>DEWAN KELUARGA MASJID AL-HIKMAH<br>Jl. Komp. Puri Cipageran Indah 2 RW 21</span>
                </td>
            </tr>
            <tr>
                <td style="font-weight: bold;text-align: center;font-size:12px;border: 1px #000 solid; width: 50%;">
                    PERJANJIAN MURABAHAH
                </td>
                <td style="font-weight: bold;text-align:center;font-size:12px; border: 1px #000 solid;">
                    No. Dokumen: <span style="font-weight:normal;">{{ $noDokumen }}</span>
                </td>
            </tr>
        </table>

        <div class="pasal-title">
            LAMPIRAN [2]<br>
            SPESIFIKASI BARANG
        </div>

        <table style="width: 100%; border-collapse: collapse; margin-bottom: 20px;" border="1">
            <tr>
                <td style="padding: 5px; width: 25%;">Nama Supplier</td>
                <td style="padding: 5px;">: {{ $namaPemasok }}</td>
            </tr>
            <tr>
                <td style="padding: 5px;">Lokasi /Alamat</td>
                <td style="padding: 5px;">: {{ $alamatPemasok }}</td>
            </tr>
        </table>

        <table style="width: 100%; border-collapse: collapse; text-align: center;" border="1">
            <thead>
                <tr>
                    <th style="padding: 5px; width: 10%;">No</th>
                    <th style="padding: 5px; width: 40%;">Jenis Barang</th>
                    <th style="padding: 5px; width: 15%;">Jumlah Unit</th>
                    <th style="padding: 5px; width: 20%;">Harga Beli Per Unit</th>
                    <th style="padding: 5px; width: 15%;">Total</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td style="padding: 5px;">1.</td>
                    <td style="padding: 5px;">{{ $pembiayaan->objekPembiayaan->nama_barang ?? '' }}</td>
                    <td style="padding: 5px;">{{ $kuantitas }}</td>
                    <td style="padding: 5px;">Rp {{ number_format($hargaBeliPerUnit, 0, ',', '.') }}</td>
                    <td style="padding: 5px;">Rp {{ number_format($totalHargaBeli, 0, ',', '.') }}</td>
                </tr>
            </tbody>
        </table>
    </div>
</body>
</html>
