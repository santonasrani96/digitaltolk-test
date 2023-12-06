<?php

namespace DTApi\Http\Controllers;

use DTApi\Models\Job;
use DTApi\Http\Requests;
use DTApi\Models\Distance;
use Illuminate\Http\Request;
use DTApi\Repository\BookingRepository;
use DTApi\Repository\NotificationRepository;

/**
 * Class BookingController
 * @package DTApi\Http\Controllers
 */
class BookingController extends Controller
{

    /**
     * @var BookingRepository
     * @var NotificationRepository
     */
    protected $repository;

    /**
     * BookingController constructor.
     * @param BookingRepository $bookingRepository
     * @param NotificationRepository $notificationRepository
     */
    public function __construct(BookingRepository $bookingRepository, NotificationRepository $notificationRepository)
    {
        $this->repository = $bookingRepository;
        $this->notification = $notificationRepository;
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function index(Request $request)
    {
        if ($user_id == $reques->get('user_id')) {
            $response = $this->repository->getUsersJobs($user_id);
        }

        if (
            $request->__authenticatedUser->user_type == env('ADMIN_ROLE_ID') ||
            $request->__authenticatedUser->user_type == env('SUPERADMIN_ROLE_ID')
        ) {
            $response = $this->repository->getAll($request);
        }
        
        return response($response);
    }

    /**
     * @param $id
     * @return mixed
     */
    public function show($id)
    {
        $job = $this->repository->with('translatorJobRel.user')->find($id);
        return response($job);
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function store(Request $request)
    {
        $response = $this->repository->store($request->__authenticatedUser, $request->all());

        return response($response);
    }

    /**
     * @param $id
     * @param Request $request
     * @return mixed
     */
    public function update($id, Request $request)
    {
        $response = $this->repository->updateJob($id, array_except($request->all(), ['_token', 'submit']), $request->__authenticatedUser);

        return response($response);
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function immediateJobEmail(Request $request)
    {
        $response = $this->repository->storeJobEmail($request->all());
        return response($response);
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function getHistory(Request $request)
    {
        return $user_id == $request->get('user_id') ? response($this->repository->getUsersJobsHistory($user_id, $request)) : null;
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function acceptJob(Request $request)
    {
        $response = $this->repository->acceptJob($request->all(), $request->__authenticatedUser);

        return response($response);
    }

    public function acceptJobWithId(Request $request)
    {
        $data = $request->get('job_id');
        $response = $this->repository->acceptJobWithId($data, $request->__authenticatedUser);

        return response($response);
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function cancelJob(Request $request)
    {
        $response = $this->repository->cancelJobAjax($request->all(), $request->__authenticatedUser);

        return response($response);
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function endJob(Request $request)
    {
        $response = $this->repository->endJob($request->all());
        return response($response);
    }

    public function customerNotCall(Request $request)
    {
        $response = $this->repository->customerNotCall($request->all());
        return response($response);
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function getPotentialJobs(Request $request)
    {
        $response = $this->repository->getPotentialJobs($request->__authenticatedUser);

        return response($response);
    }

    public function distanceFeed(Request $request)
    {
        $data = $request->all();

        $distance = isset($data['distance']) ? $data['distance'] : "";
        $time = isset($data['time']) ? $data['time'] : "";
        $jobid = isset($data['jobid']) ? $data['jobid'] : "";
        $session = isset($data['session_time']) ? $data['session_time'] : "";
        $manually_handled = $data['manually_handled'] == 'true' ? 'yes' : 'no';
        $by_admin = $data['by_admin'] == 'true' ? 'yes' : 'no';
        $admincomment = isset($data['admincomment']) ? $data['admincomment'] : "";

        if ($data['flagged'] == 'true') {
            if(empty($data['admincomment'])) {
                return "Please, add comment";
            }
            $flagged = 'yes';
        } else {
            $flagged = 'no';
        }
        
        if ($time || $distance) {
            Distance::where('job_id', '=', $jobid)->update(['distance' => $distance, 'time' => $time]);
        }

        if ($admincomment || $session || $flagged || $manually_handled || $by_admin) {
            Job::where('id', '=', $jobid)->update([
                    'admin_comments' => $admincomment,
                    'flagged' => $flagged,
                    'session_time' => $session,
                    'manually_handled' => $manually_handled,
                    'by_admin' => $by_admin
                ]);
        }

        return response('Record updated!');
    }

    public function reopen(Request $request)
    {
        $response = $this->repository->reopen($request->all());
        return response($response);
    }

    public function resendNotifications(Request $request)
    {
        $data = $request->all();
        $job = $this->repository->find($data['jobid']);
        $job_data = $this->repository->jobToData($job);
        $this->notification->sendNotificationTranslator($job, $job_data, '*');

        return response(['success' => 'Push sent']);
    }

    /**
     * Sends SMS to Translator
     * @param Request $request
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     */
    public function resendSMSNotifications(Request $request)
    {
        $data = $request->all();
        $job = $this->repository->find($data['jobid']);
        $job_data = $this->repository->jobToData($job);

        try {
            $this->notification->sendSMSNotificationToTranslator($job);
            return response(['success' => 'SMS sent']);
        } catch (\Exception $e) {
            return response(['success' => $e->getMessage()]);
        }
    }

}
