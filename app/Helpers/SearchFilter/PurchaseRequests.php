<?php

namespace App\Helpers\SearchFilter;

use App\Models\User;
use Carbon\Carbon;

class PurchaseRequests
{
    protected $model=null;
    public function __construct()
    {
        $this->model = User::query();
    }

    public function searchable()
    {
        $this->noSubs();
        $this->searchColumns();
        $this->byCreatedAt();
        $this->bySubscription();
        $this->announcement();
        $this->sortBy();

        $per_page = Request()->per_page;
        // $this->model->distinct();
        if ($per_page == '-1') {
            return $this->model->paginate($this->model->count());
        } else {
            if ($per_page) {
                return $this->model->paginate($per_page);
            } else {
                if (Request()->announcement) {
                    // $userCount = $this->model->count();
                    // $all = $this->model->limit(20)->get(); //limit if get all
                    // $result['data'] = $all;
                    // $result['total_users'] = $userCount;

                    $result['data'] = $this->model->limit(20)->get(); //limit if get all
                    $result['total_users'] = $result['data']->count();

                    return $result;
                } else {
                    return $this->model->paginate(20);
                }
            }
        }
    }
    public function noSubs()
    {
        if (Request()->noSubs) {
            $this->model->doesnthave('activeSubscription');
        }
    }
    public function searchColumns()
    {
        $searchable = ['fullname', 'email'];
        if (Request()->keyword) {
            $keyword = Request()->keyword;
            $this->model->where(function ($q) use ($keyword, $searchable) {
                foreach ($searchable as $column) {
                    if ($column == 'fullname')
                        $q->orWhereRaw("CONCAT(TRIM(`firstname`),' ', TRIM(`lastname`)) LIKE " . "'%" . $keyword . "%'");
                    else
                        $q->orWhere($column, 'LIKE', "%" . $keyword . "%");
                }
            });
        }
    }

    public function bySubscription()
    {
        if (Request()->subscription || Request()->sub_start_date || Request()->sub_end_date)
            $this->model->join('subscriptions', 'subscriptions.user_id', 'users.id')->where('subscriptions.stripe_status', Subscription::ACTIVE)
                ->select('users.*');

        if (Request()->subscription)
            $this->model->where('subscriptions.plan_id', Request()->subscription);


        if (Request()->sub_start_date) {
            $date = explode(',', Request()->sub_start_date);
            if (empty($date[1])) {
                $date[1] = $date[0];
            }
            $this->model->whereBetween('subscriptions.start_at', [Carbon::parse($date[0])->startOfDay(), Carbon::parse($date[1])->endOfDay()]);
        }
        if (Request()->sub_end_date) {
            $date = explode(',', Request()->sub_end_date);
            if (empty($date[1])) {
                $date[1] = $date[0];
            }
            $this->model->whereBetween('subscriptions.ends_at', [Carbon::parse($date[0])->startOfDay(), Carbon::parse($date[1])->endOfDay()]);
        }
    }

    public function byCreatedAt()
    {
        if (Request()->created_at) {
            $date = explode(',', Request()->created_at);
            if (empty($date[1])) {
                $date[1] = $date[0];
            }
            $this->model->whereBetween('users.created_at', [Carbon::parse($date[0])->startOfDay(), Carbon::parse($date[1])->endOfDay()]);
        }
    }

    public function sortBy()
    {
        if (Request()->sortBy) {
            $sortByFilters = explode(',', Request()->sortBy);
            foreach ($sortByFilters as $key => $filter) {
                if (empty($filter)) continue;

                [$exactSortKey, $exactSortType] = explode('/', $filter);
                if ($exactSortKey == 'fullname') {
                    $this->model->orderBy('firstname', $exactSortType);
                } elseif ($exactSortKey == 'subscription') {
                    $this->model->orderBy(
                        SubscribeUser::join('subscriptions', 'subscriptions.id', '=', 'subscriptions.subscription_id')
                            ->select('subscriptions.name')->where('subscriptions.stripe_status', Subscription::ACTIVE)
                            ->whereColumn('subscriptions.user_id', 'users.id'),
                        $exactSortType
                    );
                } elseif ($exactSortKey == 'verified') {
                    $this->model->orderBy('email_verified_at', $exactSortType);
                } else {
                    $this->model->orderBy($exactSortKey, $exactSortType);
                }
            }
        } else {
            $this->model->orderBy('created_at', 'desc');
        }
    }

    public function announcement()
    {
        if (Request()->announcement) {
            $this->model->join('users_fcm_token', 'users_fcm_token.user_id', 'users.id')
                // $this->model->rightjoin('users_fcm_token', 'users_fcm_token.user_id', 'users.id')
                ->select('users.*');
            // $this->model->whereHas('fcm_token');

            // if (Request()->subs) {
            //   return UsersFcmToken::with(['']);
            //   return SubscribeUser::where('status', 1)->get();
            //   // return DB::table('subscriptions')
            //   // ->where('status', 1)
            //   // ->join('users_fcm_token', 'users_fcm_token.user_id', 'subscriptions.user_id')
            //   // ->get();
            // } else if (Request()->noSubs) {
            //   $this->model
            // }

            $daysSubsEnded = Request()->days_subs_ended;
            $daysNewSubs = Request()->days_new_subs;
            $subscription = (int) Request()->subscription_type;

            if ($subscription) {
                if ($subscription == 1) {
                    $this->model->where('users.is_subscriber', true);
                } else if ($subscription == 2) {
                    $this->model->where('users.is_subscriber', false);
                }
            }

            if ($gender = (int) Request()->gender) {
                if ($gender == 1) $this->model->where('users.gender', 'Male');
                else if ($gender == 2) $this->model->where('users.gender', 'Female');
                else if ($gender == 3) $this->model->where('users.gender', null);
            }

            if ($age = (int) Request()->age) {
                if ($age == 1) $this->model->where('users.birthdate', null);
                else if ($age == 2) $this->model->whereBetween('users.birthdate', [Carbon::now()->subYears(19)->startOfDay(), Carbon::now()]); // 0-18
                else if ($age == 3) $this->model->whereBetween('users.birthdate', [Carbon::now()->subYears(26)->startOfDay(), Carbon::now()->subYears(19)->endOfDay()]); // 19-25
                else if ($age == 4) $this->model->whereBetween('users.birthdate', [Carbon::now()->subYears(36)->startOfDay(), Carbon::now()->subYears(26)->endOfDay()]); // 26-35
                else if ($age == 5) $this->model->whereBetween('users.birthdate', [Carbon::now()->subYears(46)->startOfDay(), Carbon::now()->subYears(36)->endOfDay()]); // 36-45
                else if ($age == 6) $this->model->whereBetween('users.birthdate', [Carbon::now()->subYears(56)->startOfDay(), Carbon::now()->subYears(46)->endOfDay()]); // 46-55
                else if ($age == 7) $this->model->whereBetween('users.birthdate', [Carbon::now()->subYears(66)->startOfDay(), Carbon::now()->subYears(56)->endOfDay()]); // 56-65
                else if ($age == 8) $this->model->whereBetween('users.birthdate', [Carbon::now()->subYears(76)->startOfDay(), Carbon::now()->subYears(66)->endOfDay()]); // 66-75
                else if ($age == 9) $this->model->whereBetween('users.birthdate', [Carbon::now()->subYears(86)->startOfDay(), Carbon::now()->subYears(76)->endOfDay()]); // 76-85
                else if ($age == 10) $this->model->whereBetween('users.birthdate', [Carbon::now()->subYears(101)->startOfDay(), Carbon::now()->subYears(86)->endOfDay()]); // 86-100
            }

            if ($daysSubsEnded || $daysNewSubs) {
                $this->model->join('subscriptions', 'subscriptions.user_id', 'users.id');
                // $this->model->leftjoin('subscriptions', 'subscriptions.user_id', 'users.id');

                $this->model->where(function ($q) use ($daysSubsEnded, $daysNewSubs) {
                    if ($daysSubsEnded) {
                        $date = explode(',', $daysSubsEnded);
                        $q->orWhereBetween('subscriptions.end_date', [Carbon::parse($date[0])->startOfDay(), Carbon::parse($date[1])->endOfDay()]);
                    }
                    if ($daysNewSubs) {
                        $date = explode(',', $daysNewSubs);
                        $q->orWhereBetween('subscriptions.start_date', [Carbon::parse($date[0])->startOfDay(), Carbon::parse($date[1])->endOfDay()]);
                    }
                });
            }

            if ($programEnded = (int) Request()->program_ended) {
                $this->model->join('user_programs', 'user_programs.user_id', 'users.id');
                // $this->model->leftjoin('user_programs', 'user_programs.user_id', 'users.id');

                $this->model->where('user_programs.program_id', $programEnded)
                    ->where('user_programs.completed_percent', 100);
            }
        }
    }
}