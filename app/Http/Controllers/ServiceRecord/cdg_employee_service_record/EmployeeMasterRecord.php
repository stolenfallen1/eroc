<?php

namespace App\Http\Controllers\ServiceRecord\cdg_employee_service_record;
use Carbon\Carbon;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Helpers\Service_Record\UseDatabaseNormalQuery;
use App\Helpers\Service_Record\UseStoredProcedure;
use App\Helpers\Service_Record\UserRequestProcessing;

class EmployeeMasterRecord extends Controller {
    protected $request_handler;
    protected $use_query;
    protected $sp;
    public function __construct(UserRequestProcessing $request_handler, UseDatabaseNormalQuery $use_query, UseStoredProcedure $sp) {
        $this->request_handler = $request_handler;
        $this->use_query = $use_query;
        $this->sp = $sp;
    }
    public function getEmployeeServiceRecords() {
        $recieved_userRequest = $this->getUserRequest();
        $userRequest = $this->request_handler->extractRequestDate($recieved_userRequest);
        try {
            $service_record = $this->use_query->employeeServiceRecordQuery($userRequest);
            if (empty($service_record)) {
                throw new \Exception('No record found');
            }
            return response()->json($service_record, 200);

        } catch (\Exception $e) {
            try {
                $service_record = $this->sp->getEmployeeServiceRecords($userRequest);
                if (empty($service_record)) {
                    throw new \Exception('No record found');
                }
                return response()->json($service_record, 200);

            } catch (\Exception $fallbackException) {
                return response()->json(['error' => 'Unable to fetch employee service records.'], 500);
            }
        }
    }
    public function getEmployeeUnderTime() {
        $recieved_userRequest = $this->getUserRequest();
        $userRequest = $this->request_handler->extractRequestDate($recieved_userRequest);
        try {
            $employee_undertime = $this->use_query->employeeUndetimeQuery($userRequest);
            if (empty($employee_undertime)) {
                throw new \Exception('No record found');
            }
            return response()->json($employee_undertime, 200);

        } catch (\Exception $e) {
            try {
                $employee_undertime = $this->sp->getEmployeeUndertimeRecord($userRequest);
                if (empty($employee_undertime)) {
                    throw new \Exception('No record found');
                }
                return response()->json($employee_undertime, 200);
            } catch (\Exception $fallbackException) {
                return response()->json(['error' => 'Unable to fetch employee undertime records.'], 500);
            }
        }
    }
    public function getEmployeeTardiness() {
        $recieved_userRequest = $this->getUserRequest();
        $userRequest = $this->request_handler->extractRequestDate($recieved_userRequest);
        try {
            $employee_tardiness = $this->use_query->employeeTardinessQuery($userRequest);
            if (empty($employee_tardiness)) {
                throw new \Exception('No record found');
            }
            return response()->json($employee_tardiness, 200);

        } catch (\Exception $e) {
            try {
                $employee_tardiness = $this->sp->getEmployeeTardinessRecord($userRequest);
                if (empty($employee_tardiness)) {
                    throw new \Exception('No record found');
                }
                return response()->json($employee_tardiness, 200);
            } catch (\Exception $fallbackException) {
                return response()->json(['error' => 'Unable to fetch employee tardiness records.'], 500);
            }
        }
    }

    public function getEmployeeOT() {
        $recieved_userRequest = $this->getUserRequest();
        $userRequest = $this->request_handler->extractRequestDate($recieved_userRequest);
        try {
            $employee_ot = $this->use_query->employeeOvertimeQuery($userRequest);
            if (empty($employee_ot)) {
                throw new \Exception('No record found');
            }
            return response()->json($employee_ot, 200);

        } catch (\Exception $e) {
            try {
                $employee_ot = $this->sp->getEmployeeOTRecord($userRequest);
                if (empty($employee_ot)) {
                    throw new \Exception('No record found');
                }
                return response()->json($employee_ot, 200);
            } catch (\Exception $fallbackException) {
                return response()->json(['error' => 'Unable to fetch employee overtime records.'], 500);
            }
        }
    }

    public function getEmployeeLeaves() {
        $recieved_userRequest = $this->getUserRequest();
        $userRequest = $this->request_handler->extractRequestDate($recieved_userRequest);
        try {
            $employee_leaves = $this->sp->getEmployeeLeavesRecord($userRequest);
            if (empty($employee_leaves)) {
                throw new \Exception('No record found');
            }
            return response()->json($employee_leaves, 200);

        } catch (\Exception $e) {
            try {
                $employee_leaves = $this->use_query->employeeUsedLeaveQuery($userRequest);
                if (empty($employee_leaves)) {
                    throw new \Exception('No record found');
                }
                return response()->json($employee_leaves, 200);
            } catch (\Exception $fallbackException) {
                return response()->json(['error' => 'Unable to fetch employee leaves records.'], 500);
            }
        }
    }

    public function getPaidLeaves() {
        try{
            $paidLeaves = $this->use_query->getPaidLeaveListQuery();
            if(empty($paidLeaves)) {
                return response()->json([], 200);
            }
            return response($paidLeaves);
        } catch(\Exception $e) {
            try {
                $paidLeaves = $this->sp->getEmployeePaidLeavesRecord();
                if (empty($paidLeaves)) {
                    throw new \Exception('No record found');
                }
                return response()->json($paidLeaves, 200);
            } catch (\Exception $fallbackException) {
                return response()->json(['error' => 'Unable to fetch employee leaves records.'], 500);
            }
        }
    }
    public function getNonPaidLeave() {
        try{
            $nonPaidLeaves = $this->use_query->getNonPaidLeaveListQuery();
            if(empty($nonPaidLeaves)) {
                return response()->json([], 200);
            }
            return response($nonPaidLeaves);
        } catch(\Exception $e) {
            try {
                $nonPaidLeaves = $this->sp->getEmployeeNonPaidLeavesRecord();
                if (empty($nonPaidLeaves)) {
                    throw new \Exception('No record found');
                }
                return response()->json($nonPaidLeaves, 200);
            } catch (\Exception $fallbackException) {
                return response()->json(['error' => 'Unable to fetch employee leaves records.'], 500);
            }
        }
    }
    public function sumOfAbsencesEachDepartment() {
        $recieved_userRequest = $this->getUserRequest();
        $userRequest = $this->request_handler->extractRequestDate($recieved_userRequest);
        try {
            $sumOfAbsences = $this->use_query->sumOfAbsentQuery($userRequest);
            if (empty($sumOfAbsences)) {
                throw new \Exception('No record found');
            }
            return response()->json($sumOfAbsences, 200);
        } catch (\Exception $e) {
            try {
                $sumOfAbsences = $this->sp->getSumOfAbsencesEachDepartment($userRequest);
                if (empty($sumOfAbsences)) {
                    throw new \Exception('No record found');
                }
                return response()->json($sumOfAbsences, 200);
            } catch (\Exception $fallbackException) {
                return response()->json(['error' => 'Unable to fetch sum of absences each department.' . $fallbackException->getMessage()], 500);
            }
        }
    }
    public function getUserRequest() {
        $request = request();
        $requestParam = [
            'year'  => $request->input('year'),
            'month' => $request->input('month'),
            'empnum' => $request->input('empId')
        ];
        return $requestParam;
    }
}
