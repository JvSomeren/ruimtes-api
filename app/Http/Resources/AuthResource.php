<?php

namespace App\Http\Resources;

use App\Admin;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class AuthResource extends JsonResource
{
    private function is_admin($uid)
    {
        try
        {
            Admin::where('uid', $uid)->firstOrFail();

            return true;
        } catch(ModelNotFoundException $e)
        {
            return false;
        }
    }

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request
     * @return array
     */
    public function toArray($request)
    {
        return [
            // 'name'        => $this->name,
            'uid'         => $this->uid,
            'hash'        => $this->hash,
            'expires'     => $this->expires,
            'isAdmin'     => $this->is_admin($this->uid),
        ];
    }
}