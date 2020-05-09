<?php

namespace App\Http\Requests\V1\Post;

use Illuminate\Foundation\Http\FormRequest;

class CreatePostRequest extends FormRequest
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
        return [
            'content' => 'required|string|min:1|max:2000|',
            'image' => 'nullable|mimes:jpeg,jpg,png,gif|max:10000'
        ];
    }
}
