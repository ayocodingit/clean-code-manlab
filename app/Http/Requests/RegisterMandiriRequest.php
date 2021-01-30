<?php

namespace App\Http\Requests;

use App\Models\Pasien;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegisterMandiriRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $id = optional($this->sampel_id);
        return [
            'reg_kewarganegaraan' => 'nullable',
            'reg_sumberpasien' => 'nullable',
            'reg_nama_pasien' => 'required',
            'reg_nik' => 'max:16',
            'reg_nohp' => 'nullable|max:15',
            'kota_id' => 'required',
            'reg_alamat' => 'required',
            'reg_jk' => 'nullable',
            'status' => ['nullable', Rule::in(array_keys(Pasien::STATUSES))],
            'reg_sampel' => [
                'required',
                "unique:sampel,nomor_sampel,$id,id,deleted_at,NULL",
                'regex:/^'.Sampel::NUMBER_FORMAT_MANDIRI.'$/',
            ],
        ];
    }
}
