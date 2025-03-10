<?php

namespace App\Resources\Tada;

use Illuminate\Http\Resources\Json\JsonResource;

class TadaResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'title' => ucfirst($this->title),
            'total_expense' => ($this->total_expense),
            'status' => ucfirst($this->status),
            'remark' => ($this->remark) ?? 'N/A',
            'employee' => ($this->whenLoaded('employeeDetail')) ? ucfirst($this->employeeDetail->name) : '',
            'submitted_date' => date('M d Y',strtotime($this->created_at)),
        ];
    }
}
