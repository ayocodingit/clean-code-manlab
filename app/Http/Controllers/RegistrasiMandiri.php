<?php

namespace App\Http\Controllers;

use App\Exports\AjaxTableExport;
use App\Models\PasienRegister;
use App\Models\Sampel;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class RegistrasiMandiri extends Controller
{
    protected $headerExport = [
        'No',
        'No Registrasi',
        'Kode Sampel',
        'Kategori',
        'Status',
        'Nama Pasien',
        'NIK',
        'Usia',
        'Satuan',
        'Tempat Lahir',
        'Tanggal Lahir',
        'Jenis Kelamin',
        'Provinsi',
        'Kota',
        'Kecamatan',
        'Kelurahan',
        'Alamat',
        'RT',
        'RW',
        'No. HP',
    ];

    public function getData(Request $request, $isData = false)
    {
        $models = $this->query();

        $params = $request->get('params', false);
        $order = $request->get('order', 'name');
        $order_direction = $request->get('order_direction', 'asc');

        if ($params) {
            $models = $this->params($params, $models);
        }

        if ($order) {
            $order = $this->order($order, $order_direction, $models);
        }

        $count = $models->count('register.nomor_register');

        $page = $request->get('page', 1);
        $perpage = $request->get('perpage', 500);

        $models = $models->skip(($page - 1) * $perpage)->take($perpage)->get();

        $result = [
            'data' => $models,
            'count' => $count,
        ];

        return !$isData ? response()->json($result) : $models;
    }

    public function exportMandiri(Request $request)
    {
        $recordMandiri = $this->getDataExport($request);

        $this->header[] = 'Kunjungan Ke';
        $this->header[] = 'Tanggal Registrasi';

        $mapping = function ($model) {
            return [
                $model->no,
                $model->nomor_register,
                $model->nomor_sampel,
                $model->sumber_pasien,
                $model->status ? STATUSES[$model->status] : null,
                $model->nama_lengkap,
                "'" . $model->nik,
                usiaPasien($model->tanggal_lahir, $model->usia_tahun),
                'Tahun',
                $model->tempat_lahir,
                parseDate($model->tanggal_lahir),
                $model->jenis_kelamin,
                $model->provinsi,
                $model->nama_kota,
                $model->kecamatan,
                $model->kelurahan,
                $model->alamat_lengkap,
                $model->no_rt,
                $model->no_rw,
                $model->no_hp ?? $model->no_telp,
                $model->kunjungan_ke,
                parseDate($model->created_at),
            ];
        };
        $column_format = [
        ];

        return Excel::download(new AjaxTableExport($recordMandiri, $this->header, $mapping, $column_format, 'Registrasi Mandiri', 'V', $models->count()), 'Registrasi-Mandiri-' . time() . '.xlsx');
    }

    public function exportRujukan(Request $request)
    {
        $models = $this->getDataExport($request);
        $this->header[] = 'Instansi Pengirim';
        $this->header[] = 'Nama Fasyankes/Dinkes';
        $this->header[] = 'Dokter';
        $this->header[] = 'Telp Fasyankes';
        $this->header[] = 'Kunjungan Ke';
        $this->header[] = 'Tanggal Registrasi';

        $mapping = function ($model) {
            return [
                $model->no,
                $model->nomor_register,
                $model->nomor_sampel,
                $model->sumber_pasien,
                $model->status ? STATUSES[$model->status] : null,
                $model->nama_lengkap,
                "'" . $model->nik,
                usiaPasien($model->tanggal_lahir, $model->usia_tahun),
                'Tahun',
                $model->tempat_lahir,
                parseDate($model->tanggal_lahir),
                $model->jenis_kelamin,
                $model->provinsi,
                $model->nama_kota,
                $model->kecamatan,
                $model->kelurahan,
                $model->alamat_lengkap,
                $model->no_rt,
                $model->no_rw,
                $model->no_hp ?? $model->no_telp,
                str_replace("_", " ", $model->fasyankes_pengirim),
                $model->nama_rs,
                $model->nama_dokter,
                $model->no_telp,
                $model->kunjungan_ke,
                parseDate($model->created_at),
            ];
        };
        $column_format = [
        ];

        return Excel::download(new AjaxTableExport($models, $this->header, $mapping, $column_format, 'Registrasi Rujukan', 'Z', $models->count()), 'Registrasi-Rujukan-' . time() . '.xlsx');
    }

    public function getDataExport(Request $request)
    {
        $models = $this->getData($request, true);
        $no = (int)($request->get('page', 1) - 1) * $request->get('perpage', 500) + 1;
        foreach ($models as $idx => &$model) {
            $model->no = $no++;
        }
        return $models;
    }

    public function query()
    {
        return PasienRegister::leftJoin('register', 'register.id', 'pasien_register.register_id')
        ->leftJoin('pasien', 'pasien.id', 'pasien_register.pasien_id')
        ->leftJoin('fasyankes', 'fasyankes.id', 'register.fasyankes_id')
        ->leftJoin('kota', 'kota.id', 'pasien.kota_id')
        ->leftJoin('provinsi', 'pasien.provinsi_id', 'provinsi.id')
        ->leftJoin('kecamatan', 'pasien.kecamatan_id', 'kecamatan.id')
        ->leftJoin('kelurahan', 'pasien.kelurahan_id', 'kelurahan.id')
        ->leftJoin('sampel', 'sampel.register_id', 'register.id')
        ->where('sampel.is_from_migration', false)
        ->where('pasien.is_from_migration', false)
        ->where('pasien_register.is_from_migration', false)
        ->where('register.is_from_migration', false)
        ->whereNull('sampel.deleted_at')
        ->whereNull('register.deleted_at');
    }

    public function params($params, $models)
    {
        foreach (json_decode($params) as $key => $val) {
            if ($val == '') {
                continue;
            }

            switch ($key) {
                case "nama_pasien":
                    $models = $models->where('pasien.nama_lengkap', 'ilike', '%' . $val . '%');
                    break;
                case "nomor_register":
                    $models = $models->where('register.nomor_register', 'ilike', '%' . $val . '%');
                    break;
                case "start_date":
                    $models = $models->where('register.created_at', '>=', substr($val, 0, 10) . ' 00:00:00');
                    break;
                case "end_date":
                    $models = $models->where('register.created_at', '<=', substr($val, 0, 10) . ' 23:59:59');
                    break;
                case "sumber_pasien":
                    $models = $models->where('register.sumber_pasien', $val);
                    break;
                // case "sumber_sampel":
                // case "nama_rs":
                //     $models = $models->where("register.nama_rs", 'ilike', '%' . $val . '%');
                //     break;
                // case "nama_rs_id":
                //     $models = $models->where("register.fasyankes_id", $val);
                //     break;
                // case "nama_rs_lainnya":
                //     $models = $models->where("register.other_nama_rs", 'ilike', '%' . $val . '%');
                //     break;
                // case "kota":
                //     $models = $models->where('kota.id', $val);
                //     break;
                // case "kategori":
                // case "kategori_isian":
                //     $models = $models->where('register.sumber_pasien', 'ilike', "%$val%");
                //     break;
                // case "start_nomor_sampel":
                //     $models = $this->nomorSampel($val, '>=', $models);
                //     break;
                // case "end_nomor_sampel":
                //     $models = $this->nomorSampel($val, '<=', $models);
                //     break;
                // case "reg_fasyankes_pengirim":
                //     $models = $models->where('register.fasyankes_pengirim', 'ilike', $val);
                //     break;
            }
        }
        return $models;
    }

    // public function order($order, $order_direction, $models)
    // {
    //     switch ($order) {
    //         case 'nama_lengkap':
    //         case 'nama_pasien':
    //             $models = $models->orderBy('pasien.nama_lengkap', $order_direction);
    //             break;
    //         case 'created_at':
    //         case 'tgl_input':
    //             $models = $models->orderBy('register.created_at', $order_direction);
    //             break;
    //         case 'nomor_register':
    //             $models = $models->orderBy('register.nomor_register', $order_direction);
    //             break;
    //         case 'nama_kota':
    //             $models = $models->orderBy('kota.nama', $order_direction);
    //             break;
    //         case 'sumber_pasien':
    //             $models = $models->orderBy('register.sumber_pasien', $order_direction);
    //             break;
    //         case 'nama_rs':
    //             $models = $models->orderBy('register.nama_rs', $order_direction);
    //             break;
    //         case 'no_sampel':
    //             $models = $models->orderBy('sampel.nomor_sampel', $order_direction);
    //             break;
    //         case 'status':
    //             $models = $models->orderBy('pasien.status', $order_direction);
    //             break;
    //         default:
    //             break;
    //     }
    //     return $models;
    // }

    public function nomorSampel($value, $operator, $models)
    {
        if (preg_match('{^' . Sampel::NUMBER_FORMAT . '$}', $value)) {
            $str = $value;
            $n = 1;
            $start = $n - strlen($str);
            $str1 = substr($str, $start);
            $str2 = substr($str, 0, $n);
            $models->whereRaw("sampel.nomor_sampel ilike '%$str2%'");
            $models->whereRaw("right(sampel.nomor_sampel,-1)::bigint $operator $str1");
        } else {
            $models->whereNull('sampel.nomor_sampel');
        }
        return $models;
    }

}
