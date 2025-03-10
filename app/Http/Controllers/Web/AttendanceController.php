<?php

namespace App\Http\Controllers\Web;

use App\Exports\AttendanceDayWiseExport;
use App\Exports\AttendanceExport;
use App\Exports\AttendanceReportExport;
use App\Helpers\AppHelper;
use App\Helpers\AttendanceHelper;
use App\Helpers\NepaliDate;
use App\Helpers\SMPush\SMPushHelper;
use App\Http\Controllers\Controller;
use App\Repositories\BranchRepository;
use App\Repositories\CompanyRepository;
use App\Repositories\RouterRepository;
use App\Repositories\UserRepository;
use App\Requests\Attendance\AttendanceNightTimeEditRequest;
use App\Requests\Attendance\AttendanceTimeAddRequest;
use App\Requests\Attendance\AttendanceTimeEditRequest;
use App\Services\Attendance\AttendanceLogService;
use App\Services\Attendance\AttendanceService;
use Carbon\Carbon;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Exception\FirebaseException;
use Kreait\Firebase\Exception\MessagingException;
use Maatwebsite\Excel\Excel;

class AttendanceController extends Controller
{
    private $view = 'admin.attendance.';

    public function __construct(protected CompanyRepository $companyRepo,
                                protected AttendanceService $attendanceService,
                                protected RouterRepository  $routerRepo,
                                protected UserRepository $userRepository,
                                protected BranchRepository $branchRepo,
                                protected AttendanceLogService $attendanceLogService,
    )
    {}

    public function index(Request $request)
    {
        $this->authorize('list_attendance');
        try {
            $appTimeSetting = AppHelper::check24HoursTimeAppSetting();
            $isBsEnabled = AppHelper::ifDateInBsEnabled();
            $selectBranch = ['id','name'];
            $companyId = AppHelper::getAuthUserCompanyId();
            $filterParameter = [
                'attendance_date' => $request->attendance_date ?? AppHelper::getCurrentDateInYmdFormat(),
                'company_id' => $companyId,
                'branch_id' => $request->branch_id ?? null,
                'department_id' => $request->department_id ?? null,
                'download_excel' => $request->download_excel,
                'date_in_bs' => false,
            ];

            if(AppHelper::ifDateInBsEnabled()){
                $filterParameter['attendance_date'] = $request->attendance_date ?? AppHelper::getCurrentDateInBS();
                $filterParameter['date_in_bs'] = true;
            }

            $attendanceDetail = $this->attendanceService->getAllCompanyEmployeeAttendanceDetailOfTheDay($filterParameter);

            $branch = $this->branchRepo->getLoggedInUserCompanyBranches($companyId,$selectBranch);
            $multipleAttendance = AppHelper::getAttendanceLimit();
            $attendanceNote = AppHelper::ifAttendanceNoteEnabled();


            if($filterParameter['download_excel']){
                return \Maatwebsite\Excel\Facades\Excel::download( new AttendanceDayWiseExport($attendanceDetail,$filterParameter, $multipleAttendance, $isBsEnabled),'attendance-'.$filterParameter['attendance_date'].'-report.xlsx');
            }


            return view($this->view . 'index', compact('attendanceDetail', 'filterParameter','branch' ,'isBsEnabled', 'appTimeSetting','multipleAttendance','attendanceNote'));
        } catch (Exception $exception) {
            return redirect()->back()->with('danger', $exception->getMessage());
        }
    }

    public function checkInEmployee($companyId, $userId): RedirectResponse
    {
        $this->authorize('attendance_create');
        try {
            $this->checkIn($userId, $companyId);
            return redirect()->back()->with('success', __('message.check_in'));
        } catch (Exception $exception) {
            return redirect()->back()->with('danger', $exception->getMessage());
        }
    }


    public function checkOutEmployee($companyId, $userId): RedirectResponse
    {
        $this->authorize('attendance_update');
        try {
            $this->checkOut($userId, $companyId);
            return redirect()->back()->with('success', __('message.check_out'));
        } catch (Exception $exception) {
            return redirect()->back()->with('danger', $exception->getMessage());
        }
    }


    public function changeAttendanceStatus($id): RedirectResponse
    {
        $this->authorize('attendance_update');
        try {
            DB::beginTransaction();
            $this->attendanceService->changeAttendanceStatus($id);
            DB::commit();
            return redirect()->back()->with('success', __('message.attendance_status_change'));
        } catch (Exception $exception) {
            return redirect()->back()->with('danger', $exception->getMessage());
        }
    }

    public function update(AttendanceTimeEditRequest $request, $id)
    {
        $this->authorize('attendance_update');
        try {
            $validatedData = $request->validated();

            $attendanceDetail = $this->attendanceService->findAttendanceDetailById($id);

            $todayAttendance = $this->attendanceService->findEmployeeTodayAttendanceDetail($attendanceDetail->user_id);


            $validatedData['is_active'] = 1;
            $with = ['branch:id,branch_location_latitude,branch_location_longitude'];
            $select = ['routers.*'];
            $userDetail = $this->userRepository->findUserDetailById($attendanceDetail->user_id);

            $routerDetail = $this->routerRepo->findRouterDetailByBranchId($userDetail->branch_id, $with, $select);

            $validatedData['worked_hour'] = 0;

            if ($validatedData['check_out_at']){


                if(!isset($attendanceDetail->check_out_at)){
                    $validatedData['check_out_latitude'] = $routerDetail->branch->branch_location_latitude;
                    $validatedData['check_out_longitude'] = $routerDetail->branch->branch_location_longitude;
                }

                $workedData = AttendanceHelper::calculateWorkedHour($validatedData['check_out_at'], $validatedData['check_in_at'],$attendanceDetail->user_id );

                $validatedData['worked_hour'] = $workedData['workedHours'];
                $validatedData['overtime'] = $workedData['overtime'];
                $validatedData['undertime'] = $workedData['undertime'];

            }

            DB::beginTransaction();
            $this->attendanceService->update($attendanceDetail, $validatedData);

            if(!isset($todayAttendance) && strtotime($attendanceDetail->attendance_date) != strtotime(date('Y-m-d'))){
                if(isset($validatedData['check_out_at'])){
                    $this->userRepository->updateUserOnlineStatus($userDetail,0);

                }
            }

            DB::commit();
            return redirect()->back()->with('success', __('message.attendance_edit'));
        }catch (Exception $exception) {
            DB::rollBack();
            return redirect()->back()->with('danger', $exception->getMessage());
        }
    }

    public function updateNightAttendance(AttendanceNightTimeEditRequest $request, $id)
    {
        $this->authorize('attendance_update');
        try {
            $validatedData = $request->validated();
            $attendanceDetail = $this->attendanceService->findAttendanceDetailById($id);
            $validatedData['is_active'] = 1;
            $with = ['branch:id,branch_location_latitude,branch_location_longitude'];
            $select = ['routers.*'];
            $userDetail = $this->userRepository->findUserDetailById($attendanceDetail->user_id);

            $routerDetail = $this->routerRepo->findRouterDetailByBranchId($userDetail->branch_id, $with, $select);
            $validatedData['worked_hour'] = 0;

            if ($validatedData['night_checkout']){
                $nightShift = AppHelper::isOnNightShift($attendanceDetail->user_id);
                $validatedData['night_shift'] = $nightShift;

                if(!isset($attendanceDetail->check_out_at)){
                    $validatedData['check_out_latitude'] = $routerDetail->branch->branch_location_latitude;
                    $validatedData['check_out_longitude'] = $routerDetail->branch->branch_location_longitude;
                }

                $workedData = AttendanceHelper::calculateWorkedHour($validatedData['night_checkout'], $validatedData['night_checkin'],$attendanceDetail->user_id );

                $validatedData['worked_hour'] = $workedData['workedHours'];
                $validatedData['overtime'] = $workedData['overtime'];
                $validatedData['undertime'] = $workedData['undertime'];

            }

            DB::beginTransaction();
            $this->attendanceService->update($attendanceDetail, $validatedData);
            $this->userRepository->updateUserOnlineStatus($userDetail,1);

            DB::commit();
            return redirect()->back()->with('success', __('message.attendance_edit'));
        }catch (Exception $exception) {
            DB::rollBack();
            return redirect()->back()->with('danger', $exception->getMessage());
        }
    }

    public function show(Request $request, $employeeId)
    {

        $this->authorize('attendance_show');
        try {
            $appTimeSetting = AppHelper::check24HoursTimeAppSetting();
            $isBsEnabled = AppHelper::ifDateInBsEnabled();
            $filterParameter = [
                'year' => $request->year ?? now()->format('Y'),
                'month' => $request->month ?? now()->month,
                'user_id' => $employeeId,
                'download_excel' => (bool)$request->get('download_excel'),
                'date_in_bs' => false,
            ];

            if($isBsEnabled){
                $nepaliDate = AppHelper::getCurrentNepaliYearMonth();
                $filterParameter['year'] = $request->year ?? $nepaliDate['year'];
                $filterParameter['month'] = $request->month ?? $nepaliDate['month'];
                $filterParameter['date_in_bs'] = true;
                $monthName = AppHelper::getNepaliMonthName($filterParameter['month']);
            }else{
                $engDate = strtotime($filterParameter['year'].'-'.$filterParameter['month'].'-01');
                $monthName  = date("F",$engDate );

            }

            $multipleAttendance = AppHelper::getAttendanceLimit();

            $months = AppHelper::MONTHS;
            $userDetail = $this->userRepository->findUserDetailById($employeeId, ['id', 'name']);

            $attendanceDetail = $this->attendanceService->getEmployeeAttendanceDetailOfTheMonth($filterParameter);

            $attendanceSummary = AttendanceHelper::getMonthlyDetail($employeeId, $filterParameter['date_in_bs'], $filterParameter['year'], $filterParameter['month']);

            if($filterParameter['download_excel']){
                if($filterParameter['date_in_bs']){
                    $month = AppHelper::getNepaliMonthName($filterParameter['month']);
                }else{
                    $month = date("F", strtotime($attendanceDetail[0]['attendance_date']));
                }

                return \Maatwebsite\Excel\Facades\Excel::download(new AttendanceExport($attendanceDetail, $userDetail,$multipleAttendance,$isBsEnabled), 'attendance-' . $userDetail->name . '-' . $filterParameter['year'] . '-' . $month . '-report.xlsx');
            }

            return view($this->view.'show',compact('attendanceDetail',
                    'filterParameter',
                    'months',
                    'userDetail',
                    'attendanceSummary',
                    'appTimeSetting',
                    'isBsEnabled',
                    'monthName',
                    'multipleAttendance',
                )
            );

        }catch (Exception $exception) {
            return redirect()->back()->with('danger', $exception->getMessage());
        }
    }

    /**
     * @throws AuthorizationException
     */
    public function delete($id)
    {
        $this->authorize('attendance_delete');
        try {

            DB::beginTransaction();
            $this->attendanceService->delete($id);
            DB::commit();
            return redirect()->back()->with('success', __('message.attendance_delete'));

        } catch (Exception $exception) {
            DB::rollBack();
           return redirect()->back()->with('danger', $exception->getMessage());
        }


    }

    public function dashboardAttendance(Request $request, $attendanceType): JsonResponse
    {
        try{
            $appTimeSetting = AppHelper::check24HoursTimeAppSetting();
            $locationDetail = [
                'lat' => $request->get('lat'),
                'long' => $request->get('long')
            ];
            $this->authorize('allow_attendance');
            $userId = getAuthUserCode();
            $companyId = AppHelper::getAuthUserCompanyId();
            $attendance = ($attendanceType == 'checkIn') ?
                $this->checkIn($userId, $companyId, true, $locationDetail) :
                $this->checkOut($userId, $companyId, true, $locationDetail);
            $message = ($attendanceType == 'checkIn') ?
                __('message.checkIn') :
                __('message.checkOut');
            $data = [
                'check_in_at' => $attendance->check_in_at ?
                    AttendanceHelper::changeTimeFormatForAttendanceAdminView($appTimeSetting, $attendance->check_in_at) : '' ,
                'check_out_at' => $attendance->check_out_at ?
                    AttendanceHelper::changeTimeFormatForAttendanceAdminView($appTimeSetting, $attendance->check_out_at) : '' ,
            ];
            return AppHelper::sendSuccessResponse($message, $data);
        } catch (Exception $exception) {
            return AppHelper::sendErrorResponse($exception->getMessage(), $exception->getCode());
        }
    }

    /**
     * @throws Exception
     */
    private function checkIn($userId, $companyId, $dashboardAttendance=false, $locationData=[])
    {
        try{
            $select = ['name'];
            $permissionKeyForNotification = 'employee_check_in';
            $userDetail = $this->userRepository->findUserDetailById($userId);
            if(!$userDetail){
                throw new Exception(__('message.employee_detail_not_found'),404);
            }
            $validatedData = $this->prepareDataForAttendance($companyId, $userDetail,'checkIn');
            if($dashboardAttendance){
                $validatedData['latitude'] = $locationData['lat'];
                $validatedData['longitude'] = $locationData['long'];
            }

            $nightShift = AppHelper::isOnNightShift($userId);
            $validatedData['night_shift'] = $nightShift;
            $validatedData['office_time_id'] = $userDetail['office_time_id'];
            $validatedData['user_id'] = $userId;

            DB::beginTransaction();
                $checkInAttendance =  $this->attendanceService->newCheckIn($validatedData);
            $this->userRepository->updateUserOnlineStatus($userDetail,1);

            DB::commit();
            AppHelper::sendNotificationToAuthorizedUser(
                __('message.checkin_notification'),
                __('message.employee_checkin',[ 'name' => ucfirst($userDetail->name),
                    'time'=> AttendanceHelper::changeTimeFormatForAttendanceView($checkInAttendance->check_in_at)]),
                $permissionKeyForNotification
            );
            return $checkInAttendance;
        }catch(Exception $exception){
            DB::rollBack();
            throw $exception;
        }

    }

    /**
     * @throws Exception
     */
    private function checkOut($userId, $companyId, $dashboardAttendance=false, $locationData=[])
    {
        try{
            $nightShift = AppHelper::isOnNightShift($userId);
            $select = ['name'];
            $permissionKeyForNotification = 'employee_check_out';
            $userDetail = $this->userRepository->findUserDetailById($userId);
            $validatedData = $this->prepareDataForAttendance($companyId, $userDetail,'checkout');
            if($dashboardAttendance){
                $validatedData['latitude'] = $locationData['lat'];
                $validatedData['longitude'] = $locationData['long'];
            }

            if($nightShift){
                $attendanceData = $this->attendanceService->findEmployeeAttendanceDetailForNightShift($userId);
            }else{
                $attendanceData = $this->attendanceService->findEmployeeTodayAttendanceDetail($userId);
            }


            if(!$attendanceData){
                return redirect()->back()->with('danger', __('message.checkin_alert'));
            }

            if($nightShift && isset($attendanceData->night_checkout)){
                return redirect()->back()->with('danger', __('message.employee_shift_checkout_alert'));

            }

            $validatedData['night_shift'] = $nightShift;
            $validatedData['user_id'] = $userId;
            $validatedData['office_time_id'] = $userDetail['office_time_id'];

            DB::beginTransaction();
                $attendanceCheckOut = $this->attendanceService->newCheckOut($attendanceData,$validatedData);

                $this->userRepository->updateUserOnlineStatus($userDetail,0);
            DB::commit();
            AppHelper::sendNotificationToAuthorizedUser(
                __('message.checkout_notification'),
                __('message.employee_checkout', [
                    'name' => ucfirst($userDetail->name),
                    'time'=> AttendanceHelper::changeTimeFormatForAttendanceView($attendanceCheckOut->check_out_at)
                ]),
                $permissionKeyForNotification
            );
            return $attendanceCheckOut;
        }catch (Exception $exception){
            DB::rollBack();
            throw $exception;
        }
    }

    /**
     * @throws Exception
     */
    private function prepareDataForAttendance($companyId, $userDetail, $checkStatus): array|RedirectResponse
    {
        $with = ['branch:id,branch_location_latitude,branch_location_longitude'];
        $select = ['routers.*'];
        $userBranchId = $userDetail->branch_id;

        $routerDetail = $this->routerRepo->findRouterDetailByBranchId($userBranchId,$with,$select);
        if (!$routerDetail) {
            throw new Exception(__('message.router_not_found'),400);
        }
        if($checkStatus == 'checkIn'){
            $validatedData['check_in_latitude'] = $routerDetail->branch->branch_location_latitude;
            $validatedData['check_in_longitude'] = $routerDetail->branch->branch_location_longitude;

        }else{
            $validatedData['check_out_latitude'] = $routerDetail->branch->branch_location_latitude;
            $validatedData['check_out_longitude'] = $routerDetail->branch->branch_location_longitude;
        }
        $validatedData['user_id'] = $userDetail->id;
        $validatedData['company_id'] = $companyId;
        $validatedData['router_bssid'] = $routerDetail->router_ssid;
        return $validatedData;
    }

    public function store(AttendanceTimeAddRequest $request)
    {
        $this->authorize('attendance_update');
        try {

            $validatedData = $request->validated();

            $userDetail = $this->userRepository->findUserDetailById($validatedData['user_id']);
            $validatedData['company_id'] = $userDetail->company_id;


            $with = ['branch:id,branch_location_latitude,branch_location_longitude'];
            $select = ['routers.*'];
            $routerDetail = $this->routerRepo->findRouterDetailByBranchId($userDetail->branch_id, $with, $select);

            $validatedData['check_in_latitude'] = $routerDetail->branch->branch_location_latitude;
            $validatedData['check_in_longitude'] = $routerDetail->branch->branch_location_longitude;
            if ($validatedData['check_out_at']){

                $validatedData['check_out_latitude'] = $routerDetail->branch->branch_location_latitude;
                $validatedData['check_out_longitude'] = $routerDetail->branch->branch_location_longitude;

                $workedData = AttendanceHelper::calculateWorkedHour($validatedData['check_out_at'], $validatedData['check_in_at'], $validatedData['user_id'] );

                if(strtotime($validatedData['check_out_at']) < strtotime($validatedData['check_in_at'])){
                    $validatedData['night_checkin'] = $validatedData['check_in_at'];
                    $validatedData['night_checkout'] = $validatedData['check_out_at'];
                    $validatedData['check_in_at'] = '';
                    $validatedData['check_out_at'] = '';
                }

                $validatedData['worked_hour'] = $workedData['workedHours'];
                $validatedData['overtime'] = $workedData['overtime'];
                $validatedData['undertime'] = $workedData['undertime'];

            }
            $validatedData['office_time_id'] = $userDetail['office_time_id'];
            DB::beginTransaction();
            $this->attendanceService->addAttendance($validatedData);
            DB::commit();
            return redirect()->back()->with('success', __('message.add_attendance'));
        }catch (Exception $exception) {
            DB::rollBack();
            return redirect()->back()->with('danger', $exception->getMessage());
        }
    }

    /**
     * @throws AuthorizationException
     */
    public function logs(){
        $this->authorize('list_attendance');
        try {

            $logData = $this->attendanceLogService->getAttendanceLog();
            return view($this->view . 'log', compact('logData'));
        } catch (Exception $exception) {
            return redirect()->back()->with('danger', $exception->getMessage());
        }
    }

    public function export(Request $request){
        try {

            $isBsEnabled = AppHelper::ifDateInBsEnabled();

            $attendanceData = [];
            if($request->all()){

                if($isBsEnabled){
                    $request->validate([
                        'start_date' => 'required',
                        'end_date' => 'required',
                    ]);

                    $startDate = \Carbon\Carbon::createFromFormat('Y-m-d', AppHelper::getEnglishDate($request['start_date']));
                    $endDate = \Carbon\Carbon::createFromFormat('Y-m-d', AppHelper::getEnglishDate($request['end_date']));

                }else{
                    $request->validate([
                        'attendance_date' =>  'required',
                    ]);

                    $attendance_date = $request['attendance_date'];

                    list($startDate, $endDate) = explode(' - ', $attendance_date);

                    $startDate = \DateTime::createFromFormat('m/d/Y', $startDate);
                    $endDate = \DateTime::createFromFormat('m/d/Y', $endDate);

                }
                $firstDay = $startDate->format('Y-m-d');
                $lastDay = $endDate->format('Y-m-d');

                $attendanceData = $this->attendanceService->getAttendanceExportData($firstDay,$lastDay, $isBsEnabled);

                if(count($attendanceData) > 0){
                    return \Maatwebsite\Excel\Facades\Excel::download( new AttendanceReportExport($attendanceData, $isBsEnabled),'attendance-report.xlsx');

                }else{
                   return redirect()->back()->with('danger','Attendance record not found');
                }
            }

            return view($this->view . 'export',compact('attendanceData','isBsEnabled'));
        } catch (Exception $exception) {
            return redirect()->back()->with('danger', $exception->getMessage());
        }
    }


}
