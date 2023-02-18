<?php

namespace App\Http\Requests\Procurement;

use App\Models\BuildFile\SystemSequence;
use Illuminate\Foundation\Http\FormRequest;

class PRRequest extends FormRequest
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
        $setting = SystemSequence::where('seq_description', 'like', '%Purchase Requisition Series Number%')->where('isActive', true)->first();

        if($setting->isSystem){
            return [
                'pr_Justication' => 'required',
                'pr_Transaction_Date_Required' => 'required',
                'pr_Priority_Id' => 'required',
                'invgroup_id' => 'required',
                'item_Category_Id' => 'required',
                'item_SubCategory_Id' => 'required',
            ];    
        }
        return [
            'pr_Justication' => 'required|alpha_dash',
            'pr_Transaction_Date_Required' => 'required',
            'pr_Priority_Id' => 'required',
            'invgroup_id' => 'required',
            'item_Category_Id' => 'required',
            'item_SubCategory_Id' => 'required',
            'pr_Document_Prefix' => 'required',
            'pr_Document_Number' => 'required',
            'pr_Document_Suffix' => 'required',
        ];
    }
}
