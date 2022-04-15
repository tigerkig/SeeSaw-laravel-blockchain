<?php

namespace App\Http\Controllers\Admin;
/**
 * Users Controller
 *
 * @package TokenLite
 * @author Softnio
 * @version 1.1.3
 */
use Mail;
use Validator;
use App\Models\KYC;
use App\Models\User;
use App\Models\UserMeta;
use App\Models\GlobalMeta;
use App\Helpers\HubSpot;
use App\Mail\EmailToUser;
use App\Http\Controllers\Controller;
use App\Notifications\Reset2FA;
use App\Notifications\ConfirmEmail;
use App\Notifications\PasswordResetByAdmin;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;

class UsersController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     * @version 1.1
     * @since 1.0
     * @return void
     */
    public function index(Request $request, $role = '')
    {
        $role_data  = '';
        $per_page   = gmvl('user_per_page', 10);
        $order_by   = (gmvl('user_order_by', 'id')=='token') ? 'tokenBalance' : gmvl('user_order_by', 'id');
        $ordered    = gmvl('user_ordered', 'DESC');
        $is_page    = (empty($role) ? 'all' : ($role=='user' ? 'investor' : $role));

        if(!empty($role)) {
            $users = User::whereNotIn('status', ['deleted'])->where('role', $role)->orderBy($order_by, $ordered)->paginate($per_page);
        } else {
            $users = User::whereNotIn('status', ['deleted'])->orderBy($order_by, $ordered)->paginate($per_page);
        }

        if($request->s){
            $users = User::AdvancedFilter($request)
                        ->orderBy($order_by, $ordered)->paginate($per_page);
        }

        if ($request->filter) {
            $users = User::AdvancedFilter($request)
                        ->orderBy($order_by, $ordered)->paginate($per_page);
        }

        $pagi = $users->appends(request()->all());
        return view('admin.users', compact('users', 'role_data', 'is_page', 'pagi'));
    }

    /**
     * Send email to specific user
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     * @version 1.0.1
     * @since 1.0
     * @return void
     */
    public function send_email(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required',
        ], [
            'user_id.required' => __('Select a user first!'),
        ]);

        if ($validator->fails()) {
            if ($validator->errors()->has('name')) {
                $msg = $validator->errors()->first();
            } else {
                $msg = __('messages.somthing_wrong');
            }

            $ret['msg'] = 'warning';
            $ret['message'] = $msg;
        } else {
            $user = User::FindOrFail($request->input('user_id'));

            if ($user) {
                $msg = $request->input('message');
                $msg = replace_with($msg, '[[user_name]]', $user->name);
                $data = (object) [
                    'user' => (object) ['name' => $user->name, 'email' => $user->email],
                    'subject' => $request->input('subject'),
                    'greeting' => $request->input('greeting'),
                    'text' => str_replace("\n", "<br>", $msg),
                ];
                $when = now()->addMinutes(2);

                try {
                    Mail::to($user->email)
                    ->later($when, new EmailToUser($data));
                    $ret['msg'] = 'success';
                    $ret['message'] = __('messages.mail.send');
                } catch (\Exception $e) {
                    $ret['errors'] = $e->getMessage();
                    $ret['msg'] = 'warning';
                    $ret['message'] = __('messages.mail.issues');
                }
            } else {
                $ret['msg'] = 'warning';
                $ret['message'] = __('messages.mail.failed');
            }

            if ($request->ajax()) {
                return response()->json($ret);
            }
            return back()->with([$ret['msg'] => $ret['message']]);
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     * @version 1.0.2
     * @since 1.0
     * @return void
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'role' => 'required',
            'name' => 'required|min:3',
            'email' => 'required|email|unique:users,email',
            'password' => 'nullable|min:6',
        ], [
            'email.unique' => __('messages.email.unique')
        ]);

        if ($validator->fails()) {
            $msg = '';
            if ($validator->errors()->hasAny(['name', 'email', 'password'])) {
                $msg = $validator->errors()->first();
            } else {
                $msg = __('messages.somthing_wrong');
            }

            $ret['msg'] = 'warning';
            $ret['message'] = $msg;
            return response()->json($ret);
        } else {
            if($request->input('role') == 'admin' && !super_access()) {
                $ret['msg'] = 'warning';
                $ret['message'] = __("You do not have enough permissions to perform requested operation.");
            } else {
                $req_password = $request->input('password') ? $request->input('password') : str_random(12);
                $password = Hash::make($req_password);
                $lastLogin = date("Y-m-d H:i:s");
                $user = User::create([
                    'name' => $request->input('name'),
                    'email' => $request->input('email'),
                    'password' => $password,
                    'role' => $request->input('role'),
                    'lastLogin' => $lastLogin,
                ]);

                if ($user) {
                    $user->email_verified_at = isset($request->email_req) ? null : date('Y-m-d H:i:s');
                    $user->registerMethod = 'Internal';
                    // $user->referral = ($user->id.'.'.str_random(50));
                    $user->save();
                    $meta = UserMeta::create([
                        'userId' => $user->id,
                    ]);
                    $meta->notify_admin = ($request->input('role') == 'user') ? 0 : 1;
                    $meta->email_token = str_random(65);
                    $meta->email_expire = now()->addMinutes(75);
                    $meta->save();

                    $extra = (object) [
                        'name' => $user->name,
                        'email' => $user->email,
                        'password' => $req_password,
                    ];

                    try {
                        if (isset($request->email_req)) {
                            $user->notify(new ConfirmEmail($user, $extra));
                        }
                        // $user->notify(new AddUserEmail($user));
                        $ret['link'] = route('admin.users');
                        $ret['msg'] = 'success';
                        $ret['message'] = __('messages.insert.success', ['what' => 'User']);
                    } catch (\Exception $e) {
                        $ret['errors'] = $e->getMessage();
                        $ret['link'] = route('admin.users');
                        $ret['msg'] = 'warning';
                        $ret['message'] = __('messages.insert.success', ['what' => 'User']).' '.__('messages.email.failed');
                        ;
                    }
                } else {
                    $ret['msg'] = 'warning';
                    $ret['message'] = __('messages.insert.warning', ['what' => 'User']);
                }
            }

            if ($request->ajax()) {
                return response()->json($ret);
            }
            return back()->with([$ret['msg'] => $ret['message']]);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param string $id
     * @param string $type
     * @return \Illuminate\Http\Response
     * @throws \Throwable
     * @version 1.1
     * @since 1.0
     */
    public function show(Request $request, $id=null, $type=null)
    {
        if($request->ajax()){
            $id = $request->input('uid');
            $type = $request->input('req_type');
            $user = User::FindOrFail($id);
            if ($type == 'transactions') {
                $transactions = \App\Models\Transaction::where(['user' => $id, 'status' => 'approved'])->latest()->get();
                return view('modals.user_transactions', compact('user', 'transactions'))->render();
            }
            if ($type == 'activities') {
                $activities = \App\Models\Activity::where('user_id', $id)->latest()->limit(15)->get();
                return view('modals.user_activities', compact('user', 'activities'))->render();
            }
            // v1.1
            if ($type == 'referrals') {
                $refered = User::where('referral', $user->id)->get(['id', 'name', 'created_at']);
                foreach ($refered as $refer) {
                    $ref_count = User::where('referral', $refer->id)->count();
                    if($ref_count > 0){
                        $refer->refer_to = $ref_count;
                    }else{
                        $refer->refer_to = 0;
                    }
                }
                return view('modals.user_referrals', compact('user', 'refered'))->render();
            }
        }

        $user = User::FindOrFail($id);
        if ($type == 'details') {
            $refered = User::FindOrFail($id)->referrals();
            return view('admin.user_details', compact('user', 'refered'))->render();
        }
    }

    public function status(Request $request)
    {
        $id = $request->input('uid');
        $type = $request->input('req_type');
        
        if(!super_access()) {
            $up = User::where('id', $id)->first();
            if($up) {
                if($up->role!='user') {
                    $result['msg'] = 'warning';
                    $result['message'] = __("You do not have enough permissions to perform requested operation.");
                    return response()->json($result);
                }
            }
        }

        if ($type == 'suspend_user') {
            $admin_count = User::where('role', 'admin')->count();
            if ($admin_count >= 1) {
                $up = User::where('id', $id)->update([
                    'status' => 'suspend',
                ]);
                if ($up) {
                    $result['msg'] = 'warning';
                    $result['css'] = 'danger';
                    $result['status'] = 'active_user';
                    $result['message'] = 'User Suspend Success!!';
                } else {
                    $result['msg'] = 'warning';
                    $result['message'] = 'Failed to Suspend!!';
                }
            } else {
                $result['msg'] = 'warning';
                $result['message'] = 'Minimum one admin account is required!';
            }

            return response()->json($result);
        }
        if ($type == 'active_user') {
            $up = User::where('id', $id)->update([
                'status' => 'active',
            ]);
            if ($up) {
                $result['msg'] = 'success';
                $result['css'] = 'success';
                $result['status'] = 'suspend_user';
                $result['message'] = 'User Active Success!!';
            } else {
                $result['msg'] = 'warning';
                $result['message'] = 'Failed to Active!!';
            }
            return response()->json($result);
        }
        if ($type == 'reset_pwd') {
            $pwd = str_random(15);
            $up = User::where('id', $id)->first();
            $up->password = Hash::make($pwd);

            $update = (object) [
                'new_password' => $pwd,
                'name' => $up->name,
                'email' => $up->email,
                'id' => $up->id,
            ];
            if ($up->save()) {
                try {
                    $up->notify(new PasswordResetByAdmin($update));
                    $result['msg'] = 'success';
                    $result['message'] = 'Password Changed!! ';
                } catch (\Exception $e) {
                    $ret['errors'] = $e->getMessage();
                    $result['msg'] = 'warning';
                    $result['message'] = 'Password Changed!! but user was not notified. Please! check your email setting and try again.';
                }
            } else {
                $result['msg'] = 'warning';
                $result['message'] = 'Failed to Changed!!';
            }
            return response()->json($result);
        }
        if ($type == 'reset_2fa') {
            $user = User::where('id', $id)->first();
            if ($user) {
                $user->notify(new Reset2FA($user));
                $result['msg'] = 'success';
                $result['message'] = '2FA confirmation email send to the user.';
            } else {
                $ret['errors'] = $e->getMessage();
                $result['msg'] = 'warning';
                $result['message'] = 'Failed to reset 2FA!!';
            }
            return response()->json($result);
        }
    }

    /**
     * wallet change request
     *
     * @return \Illuminate\Http\Response
     * @version 1.0.0
     * @since 1.0
     */
    public function wallet_change_request()
    {
        $meta_data = GlobalMeta::where('name', 'user_wallet_address_change_request')->get();
        return view('admin.user-request', compact('meta_data'));
    }

    public function wallet_change_request_action(Request $request)
    {
        $meta = GlobalMeta::FindOrFail($request->id);
        $ret['msg'] = 'info';
        $ret['message'] = __('messages.nothing');
        if ($meta) {
            $action = $request->action;

            if ($action == 'approve') {
                $meta->user->walletType = $meta->data()->name;
                $meta->user->walletAddress = $meta->data()->address;

                $meta->user->save();
                $meta->delete();

                // send info to hubspot
                $hubSpot = new HubSpot();
                $hubSpot->put($meta->user);

                $ret['msg'] = 'success';
                $ret['message'] = __('messages.wallet.approved');
            }
            if ($action == 'reject') {
                $ret['msg'] = 'warning';
                $ret['message'] = __('messages.wallet.cancel');
                $meta->delete();
            }
        } else {
            $ret['msg'] = 'warning';
            $ret['message'] = __('messages.wallet.failed');
        }

        if ($request->ajax()) {
            return response()->json($ret);
        }
        return back()->with([$ret['msg'] => $ret['message']]);
    }

    /**
     * Delete all unverified users
     *
     * @return \Illuminate\Http\Response
     * @version 1.0.0
     * @since 1.0
     */
    public function delete_unverified_user(Request $request)
    {
        $ret['msg'] = 'info';
        $ret['message'] = __('messages.nothing');

        $user = User::where(['registerMethod' => "Email", 'email_verified_at' => NULL])->get();
        if($user->count()){
            $data = $user->each(function($item){
                $item->meta()->delete();
                $item->logs()->delete();
                $item->delete();
            });

            if($data){
                $ret['msg'] = 'success';
                $ret['message'] = __('messages.delete.delete', ['what' => 'Unvarified users']);
            }
            else{
                $ret['msg'] = 'warning';
                $ret['message'] = __('messages.delete.delete_failed', ['what' => 'Unvarified users']);
            }
        }
        else{
            $ret['msg'] = 'success';
            $ret['alt'] = 'no';
            $ret['message'] = __('There has not any unvarified users!');
        }


        if ($request->ajax()) {
            return response()->json($ret);
        }
        return back()->with([$ret['msg'] => $ret['message']]);
    }

    /**
     * Change users permission
     *
     * @return \Illuminate\Http\Response
     * @version 1.0.0
     * @since 1.0
     */

    public function users_change(Request $request)
    {
        $user_id = $request->input('user_id');
        $user_type = $request->input('type');
        User::where('id', $user_id)->update(['role' => $user_type]);

        $ret['msg'] = 'success';
        $ret['message'] = 'User is changed to ' . $user_type;
        return response()->json($ret);

    }

}
