<?php

namespace App\Repositories;

use App\Models\OfficeTime;

class OfficeTimeRepository
{

    public function getAllCompanyOfficeTime($with=[],$select=['*'])
    {
        return OfficeTime::with($with)->select($select)->latest()->get();
    }

    public function store($validatedData)
    {
        return OfficeTime::create($validatedData)->fresh();
    }

    public function findCompanyOfficeTimeById($id,$select=['*'])
    {
        return OfficeTime::select($select)->where('id',$id)->first();
    }

    public function validateTime($startTime, $endTime){
        return OfficeTime::where(  'opening_time', $startTime)->where('closing_time',$endTime)->exists();
    }

    public function getAllOnlyActiveCompanyOfficeTime($select=['*'])
    {
        return OfficeTime::select($select)->where('is_active',1)->get();
    }

    public function getALlActiveOfficeTimeByCompanyId($companyId,$select=['*'])
    {
        return OfficeTime::select($select)
            ->where('company_id',$companyId)
            ->where('is_active',1)
            ->get();
    }

    public function getALlActiveOfficeTimeByBranchId($branchId,$select=['*'])
    {
        return OfficeTime::select($select)
            ->where('branch_id',$branchId)
            ->where('is_active',1)
            ->get();
    }

    public function delete($officeTime)
    {
        return $officeTime->delete();
    }

    public function update($officeTime,$validatedData)
    {

        return $officeTime->update($validatedData);
    }

    public function toggleStatus($id)
    {
        $officeTime = OfficeTime::where('id',$id)->first();
        return $officeTime->update([
            'is_active' => !$officeTime->is_active,
        ]);
    }


}
