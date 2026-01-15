<!DOCTYPE html>
<html>
    <head>
        <style>
            body { font-family: sans-serif; font-size: 11px }
            table { width: 100%; border-collapse: collapse }
            th, td { border: 1px solid #000; padding: 5px }
            th { background: #f2f2f2 }
        </style>
    </head>

    <body>
        <h3>{{ $title }}</h3>
        <table>
            <thead>
                <tr>
                    <th>No</th>
                    <th>No Transaksi</th>
                    <th>Tanggal</th>
                    <th>Anggota</th>
                    <th>Produk</th>
                    <th>Jenis</th>
                    <th>Nominal</th>
                </tr>
            </thead>
            <tbody>
                @foreach($transactions as $i => $trx)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>TRX-{{ str_pad($trx->id, 6, '0', STR_PAD_LEFT) }}</td>
                    <td>{{ $trx->transaction_date->format('d/m/Y') }}</td>
                    <td>{{ $trx->savingAccount->user->name }}</td>
                    <td>{{ $trx->savingAccount->type }}</td>
                    <td>{{ $trx->type }}</td>
                    <td style="text-align:right">
                        Rp {{ number_format($trx->amount, 0, ',', '.') }}
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </body>
</html>