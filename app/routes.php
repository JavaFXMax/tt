<?php

Route::get('/samplepdf', function(){
      $organization= Organization::find(1);
      $members=Member::all();
      $counter=DB::table('members')->count();
      $view = \View::make('pdf.memberlist',compact('members','organization'));
      $html = $view->render();
      if($counter > 75){
            $pdf = new TCPDF('L');
      }
      if($counter <= 75){
            $pdf = new TCPDF('P');
      }
      $pdf->SetTitle('Hello World');
      $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
      $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
      $pdf->AddPage();
      $pdf->writeHTML($html, true, false, true, false, ' ');
      $pdf->Output('hello_world.pdf');
});
Route::get('/', function()
{
    $count = count(User::all());
    if($count == 0 ){
        return View::make('signup');
    }
	if (Confide::user()) {
            return Redirect::to('/dashboard');
        } else {
            return View::make('login');
        }
});
Route::get('/dashboard', function()
{
	if (Confide::user()) {
        if(Confide::user()->user_type == 'admin'){
            $members = Member::all();
            return View::make('dashboard', compact('members'));
        }

        if(Confide::user()->user_type == 'teller'){
            $members = Member::all();
            return View::make('tellers.dashboard', compact('members'));
        }

        if(Confide::user()->user_type == 'member'){
            $loans = Loanproduct::all();
            $member = Member::where('membership_no',Confide::user()->username)->first();
            $products = Product::all();
            //$rproducts = Product::getRemoteProducts();
            return View::make('css.memberindex', compact('loans', 'member', 'products'));
        }

        } else {
            return View::make('login');
        }
});
//
Route::get('member', function(){
    $member = Member::where('membership_no',Confide::user()->username)->first();
    return View::make('css.memberindex', compact('member'));
});

Route::get('memberloanrepayments', function(){
    $m = Member::where('membership_no',Confide::user()->username)->first();
    $member = Member::findOrFail($m->id);
    /*$loanaccounts = DB::table('loanaccounts')
                       ->join('loanproducts', 'loanaccounts.loanproduct_id', '=', 'loanproducts.id')
                       ->join('members', 'loanaccounts.member_id', '=', 'members.id')
                       ->where('loanaccounts.member_id',$member->id)
                       ->where('loanaccounts.is_approved',1)
                       ->select('loanaccounts.id as id','members.name as mname','members.id as mid','loanproducts.name as pname','phone','application_date','amount_applied','repayment_duration','loanaccounts.interest_rate')
                       ->get();*/
    return View::make('css.loanrepayment', compact('member'));
});

Route::post('loanpayment/{id}', function(){
   $name = Input::get('mname');
   $date = Input::get('date');
   $phone = Input::get('phone');
   $mid = Input::get('mid');
   $loanaccount_id = Input::get('loanaccount_id');
   $amount = Input::get('amount');
    View::addLocation(app_path() . '/views/pesapal-php-master');
    View::addNamespace('theme', app_path() .'/views/pesapal-php-master');
    return View::make('pesapal-iframe', compact('name','date','phone','amount','mid','loanaccount_id'));
});

Route::post('memberloanrepayments/offsetloan', function(){
   $name = Input::get('mname');
   $date = Input::get('date');
   $phone = Input::get('phone');
   $mid = Input::get('mid');
   $loanaccount_id = Input::get('loanaccount_id');
   $amount = Input::get('amount');
    View::addLocation(app_path() . '/views/pesapal-php-master');
    View::addNamespace('theme', app_path() .'/views/pesapal-php-master');
    return View::make('pesapal-iframe-offset', compact('name','date','phone','amount','mid','loanaccount_id'));
});

Route::get('/pesapal_callback', function(){
   $validator = Validator::make($data = Input::all(), Loanrepayment::$rules);
    if ($validator->fails())
    {
      return Redirect::back()->withErrors($validator)->withInput();
    }
    $loanaccount = Input::get('loanaccount_id');
    Loanrepayment::repayLoan($data);
    return Redirect::to('/memberloanrepayments')->withFlashMessage('You have successfully paid this month`s loan instalment!');
});

Route::get('/pesapal_callback_offset', function(){
   $validator = Validator::make($data = Input::all(), Loanrepayment::$rules);
    if ($validator->fails())
    {
      return Redirect::back()->withErrors($validator)->withInput();
    }
    $loanaccount = Input::get('loanaccount_id');
    Loanrepayment::offsetLoan($data);
    return Redirect::to('/memberloanrepayments')->withFlashMessage('You have successfully completed paying your loan!');
});

Route::post('membersavingtransactions/{id}', function(){
  if(Input::get('type') == 'credit'){
   $transacted_by = Input::get('transacted_by');
   $date = Input::get('date');
   $ttype = Input::get('type');
   $phone = Input::get('phone');
   $mid = Input::get('mid');
   $description = Input::get('description');
   $account_id = Input::get('account_id');
   $amount = Input::get('amount');
    View::addLocation(app_path() . '/views/pesapal-php-master');
    View::addNamespace('theme', app_path() .'/views/pesapal-php-master');
    return View::make('pesapal-iframe-savings', compact('transacted_by','description','date','phone','amount','mid','account_id','ttype'));
}else{
  $validator = Validator::make($data = Input::all(), Savingtransaction::$rules);
    if ($validator->fails())
    {
      return Redirect::back()->withErrors($validator)->withInput();
    }
    $date = Input::get('date');
    $transAmount = Input::get('amount');
    $currency = Currency::find(1);
    $savingaccount = Savingaccount::findOrFail(Input::get('account_id'));
    $date = Input::get('date');
    $amount = Input::get('amount');
    $type = Input::get('type');
    $description = Input::get('description');
    $transacted_by = Input::get('transacted_by');
    Savingtransaction::transact($date, $savingaccount, $amount, $type, $description, $transacted_by);
    return Redirect::to('memtransactions/'.$savingaccount->id)->withFlashMessage('You have successfully withdrawn '.$currency->shortname.'. '.number_format($transAmount,2).' from your savings account!');
}
});

Route::get('/pesapal_callback_saving', function(){
   $validator = Validator::make($data = Input::all(), Savingtransaction::$rules);
    if ($validator->fails())
    {
      return Redirect::back()->withErrors($validator)->withInput();
    }
    $date = Input::get('date');
    $transAmount = Input::get('amount');
    $currency = Currency::find(1);
    $savingaccount = Savingaccount::findOrFail(Input::get('account_id'));
    $date = Input::get('date');
    $amount = Input::get('amount');
    $type = Input::get('type');
    $description = Input::get('description');
    $transacted_by = Input::get('transacted_by');
    Savingtransaction::transact($date, $savingaccount, $amount, $type, $description, $transacted_by);
    return Redirect::to('memtransactions/'.$savingaccount->id)->withFlashMessage('You have successfully deposited '.$currency->shortname.'. '.number_format($amount,2).' to your savings account!');
});
Route::get('transaudits', function(){
    $transactions = Loantransaction::all();
    return View::make('transaud', compact('transactions'));
});

Route::post('transaudits', function(){
    $date = Input::get('date');
    $type = Input::get('type');
    if($type == 'loan'){
        $transactions = DB::table('loantransactions')->where('date', '=', $date)->get();
        return View::make('transaudit', compact('transactions', 'type', 'date'));
    }
    if($type == 'savings'){
        $transactions = DB::table('savingtransactions')->where('date', '=', $date)->get();
        return View::make('transaudit', compact('transactions', 'type', 'date'));
    }
});
// Confide routes
Route::get('users/create', 'UsersController@create');
Route::get('users/edit/{user}', 'UsersController@edit');
Route::post('users/update/{user}', 'UsersController@update');
Route::post('users', 'UsersController@store');
Route::get('users/login', 'UsersController@login');
Route::post('users/login', 'UsersController@doLogin');
Route::get('users/confirm/{code}', 'UsersController@confirm');
Route::get('users/forgot_password', 'UsersController@forgotPassword');
Route::post('users/forgot_password', 'UsersController@doForgotPassword');
Route::get('users/reset_password/{token}', 'UsersController@resetPassword');
Route::post('users/reset_password', 'UsersController@doResetPassword');
Route::get('users/logout', 'UsersController@logout');
Route::resource('users', 'UsersController');
Route::get('users/activate/{user}', 'UsersController@activate');
Route::get('users/deactivate/{user}', 'UsersController@deactivate');
Route::get('users/destroy/{user}', 'UsersController@destroy');
Route::get('users/password/{user}', 'UsersController@Password');
Route::post('users/password/{user}', 'UsersController@changePassword');
Route::get('users/profile/{user}', 'UsersController@profile');
Route::get('users/add', 'UsersController@add');
Route::post('users/newuser', 'UsersController@newuser');
Route::get('tellers', 'UsersController@tellers');
Route::get('tellers/create/{id}', 'UsersController@createteller');
Route::get('tellers/activate/{id}', 'UsersController@activateteller');
Route::get('tellers/deactivate/{id}', 'UsersController@deactivateteller');
Route::get('members/profile', 'UsersController@password2');
Route::post('users/pass', 'UsersController@changePassword2');
Route::get('members/deactivates', 'MembersController@deactives');
Route::resource('roles', 'RolesController');
Route::get('roles/create', 'RolesController@create');
Route::get('roles/edit/{id}', 'RolesController@edit');
Route::post('roles/update/{id}', 'RolesController@update');
Route::get('roles/delete/{id}', 'RolesController@destroy');
Route::get('import', function(){
    return View::make('import');
});

Route::get('automated/loans', function(){
    $loanproducts = Loanproduct::all();
    return View::make('autoloans', compact('loanproducts'));
});

Route::get('automated/savings', function(){
   $savingproducts = Savingproduct::all();
    return View::make('automated', compact('savingproducts'));
});

Route::post('automated', function(){
    $members = DB::table('members')->where('is_active', '=', true)->get();
    $category = Input::get('category');
    if($category == 'savings'){
        $savingproduct_id = Input::get('savingproduct');
        $savingproduct = Savingproduct::findOrFail($savingproduct_id);
            foreach($savingproduct->savingaccounts as $savingaccount){
                if(($savingaccount->member->is_active) && (Savingaccount::getLastAmount($savingaccount) > 0)){
                    $data = array(
                        'account_id' => $savingaccount->id,
                        'amount' => Savingaccount::getLastAmount($savingaccount),
                        'date' => date('Y-m-d'),
                        'type'=>'credit'
                        );
                    Savingtransaction::creditAccounts($data);
                }

    }
       Autoprocess::record(date('Y-m-d'), 'saving', $savingproduct);
    } else {
        $loanproduct_id = Input::get('loanproduct');
        $loanproduct = Loanproduct::findOrFail($loanproduct_id);
            foreach($loanproduct->loanaccounts as $loanaccount){
                if(($loanaccount->member->is_active) && (Loanaccount::getEMP($loanaccount) > 0)){
                    $data = array(
                        'loanaccount_id' => $loanaccount->id,
                        'amount' => Loanaccount::getEMP($loanaccount),
                        'date' => date('Y-m-d')
                        );
                    Loanrepayment::repayLoan($data);
                }
            }
             Autoprocess::record(date('Y-m-d'), 'loan', $loanproduct);
    }
    return Redirect::back()->with('notice', 'successfully processed');
});

Route::get('system', function(){
    $organization = Organization::find(1);
    return View::make('system.index', compact('organization'));
});

Route::get('license', function(){
    $organization = Organization::find(1);
    return View::make('system.license', compact('organization'));
});
/**
* Organization routes
*/
Route::resource('organizations', 'OrganizationsController');
Route::post('organizations/update/{id}', 'OrganizationsController@update');
Route::post('organizations/logo/{id}', 'OrganizationsController@logo');
Route::get('language/{lang}',
           array(
                  'as' => 'language.select',
                  'uses' => 'OrganizationsController@language'
                 )
          );
Route::resource('currencies', 'CurrenciesController');
Route::get('currencies/edit/{id}', 'CurrenciesController@edit');
Route::post('currencies/update/{id}', 'CurrenciesController@update');
Route::get('currencies/delete/{id}', 'CurrenciesController@destroy');
Route::get('currencies/create', 'CurrenciesController@create');
Route::get('loanrepayments/offprint/{id}', 'LoanrepaymentsController@offprint');
/*
* apartments routes
*/
Route::resource('apartments', 'ApartmentsController');
Route::get('apartments/list', 'ApartmentsController@list');
Route::get('apartments/create', 'ApartmentsController@create');
Route::get('apartments/edit/{id}', 'ApartmentsController@edit');
Route::post('apartments/update/{id}', 'ApartmentsController@update');
Route::get('apartments/show/{id}', 'ApartmentsController@show');
Route::get('apartments/delete/{id}', 'ApartmentsController@destroy');
/*
* branches routes
*/
Route::resource('branches', 'BranchesController');
Route::post('branches/update/{id}', 'BranchesController@update');
Route::get('branches/delete/{id}', 'BranchesController@destroy');
Route::get('branches/edit/{id}', 'BranchesController@edit');
Route::resource('groups', 'GroupsController');
Route::post('groups/update/{id}', 'GroupsController@update');
Route::get('groups/delete/{id}', 'GroupsController@destroy');
Route::get('groups/edit/{id}', 'GroupsController@edit');
Route::resource('members', 'MembersController');
Route::post('members/update/{id}', 'MembersController@update');
Route::post('member/update/{id}', 'MembersController@update');
Route::get('members/delete/{id}', 'MembersController@destroy');
Route::get('members/delete/{id}', array('before' => 'delete_member', 'uses' => 'MembersController@destroy'));
Route::get('members/edit/{id}', array('before' => 'update_member', 'uses' => 'MembersController@edit'));
Route::get('members/deactivate/{id}', array('before' => 'deactivate_member', 'uses' => 'MembersController@deactivate'));
Route::get('members/activate/{id}', 'MembersController@activate');
Route::get('members/show/{id}', 'MembersController@show');
Route::get('member/show/{id}', 'MembersController@show');
Route::post('deldoc', 'MembersController@deletedoc');
Route::get('members/loanaccounts/{id}', 'MembersController@loanaccounts');
Route::get('memberloans', 'MembersController@loanaccounts2');
Route::get('members/create', array('before' => 'create_member|limit', 'uses' => 'MembersController@create'));
Route::resource('kins', 'KinsController');
Route::post('kins/update/{id}', 'KinsController@update');
Route::get('kins/delete/{id}', 'KinsController@destroy');
Route::get('kins/edit/{id}', 'KinsController@edit');
Route::get('kins/show/{id}', 'KinsController@show');
Route::get('kins/create/{id}', 'KinsController@create');
Route::resource('accounts', 'AccountsController');
Route::post('accounts/update/{id}', 'AccountsController@update');
Route::get('accounts/delete/{id}', 'AccountsController@destroy');
Route::get('accounts/edit/{id}', 'AccountsController@edit');
Route::get('accounts/show/{id}', 'AccountsController@show');
Route::get('accounts/create/{id}', 'AccountsController@create');
Route::resource('vehicles', 'VehiclesController');
Route::post('vehicles/update/{id}', 'VehiclesController@update');
Route::get('vehicles/delete/{id}', 'VehiclesController@destroy');
Route::get('vehicles/edit/{id}', 'VehiclesController@edit');
Route::get('vehicles/show/{id}', 'VehiclesController@show');
Route::get('vehicles/create', 'VehiclesController@create');
Route::get('vehicles/add/{id}', 'VehiclesController@addVehicle');
Route::post('vehicles/add', 'VehiclesController@addedVehicle');

Route::resource('assignvehicles', 'AssignVehiclesController');
Route::post('assignvehicles/update/{id}', 'AssignVehiclesController@update');
Route::get('assignvehicles/delete/{id}', 'AssignVehiclesController@destroy');
Route::get('assignvehicles/edit/{id}', 'AssignVehiclesController@edit');
Route::get('assignvehicles/show/{id}', 'AssignVehiclesController@show');
Route::get('assignvehicles/create', 'AssignVehiclesController@create');

Route::resource('vehicleincomes', 'VehicleIncomesController');
Route::post('vehicleincomes/update/{id}', 'VehicleIncomesController@update');
Route::get('vehicleincomes/delete/{id}', 'VehicleIncomesController@destroy');
Route::get('vehicleincomes/edit/{id}', 'VehicleIncomesController@edit');
Route::get('vehicleincomes/show/{id}', 'VehicleIncomesController@show');
Route::get('vehicleincomes/create', 'VehicleIncomesController@create');

Route::resource('vehicleexpenses', 'VehicleExpensesController');
Route::post('vehicleexpenses/update/{id}', 'VehicleExpensesController@update');
Route::get('vehicleexpenses/delete/{id}', 'VehicleExpensesController@destroy');
Route::get('vehicleexpenses/edit/{id}', 'VehicleExpensesController@edit');
Route::get('vehicleexpenses/show/{id}', 'VehicleExpensesController@show');
Route::get('vehicleexpenses/create', 'VehicleExpensesController@create');

Route::resource('journals', 'JournalsController');
Route::post('journals/update/{id}', 'JournalsController@update');
Route::get('journals/delete/{id}', 'JournalsController@destroy');
Route::get('journals/edit/{id}', 'JournalsController@edit');
Route::get('journals/show/{id}', 'JournalsController@show');

Route::resource('charges', 'ChargesController');
Route::post('charges/update/{id}', 'ChargesController@update');
Route::get('charges/delete/{id}', 'ChargesController@destroy');
Route::get('charges/edit/{id}', 'ChargesController@edit');
Route::get('charges/show/{id}', 'ChargesController@show');
Route::get('charges/disable/{id}', 'ChargesController@disable');
Route::get('charges/enable/{id}', 'ChargesController@enable');

Route::resource('savingproducts', 'SavingproductsController');
Route::post('savingproducts/update', 'SavingproductsController@update');
Route::get('savingproducts/delete/{id}', 'SavingproductsController@destroy');
Route::get('savingproducts/edit/{id}', 'SavingproductsController@edit');
Route::get('savingproducts/show/{id}', 'SavingproductsController@show');

Route::resource('savingaccounts', 'SavingaccountsController');
Route::get('savingaccounts/create/{id}', 'SavingaccountsController@create');
Route::get('member/savingaccounts/{id}', 'SavingaccountsController@memberaccounts');

Route::get('savingtransactions/show/{id}', 'SavingtransactionsController@show');
Route::resource('savingtransactions', 'SavingtransactionsController');
Route::get('savingtransactions/create/{id}', 'SavingtransactionsController@create');
Route::get('membersavingtransactions/create/{id}', 'SavingtransactionsController@create');
Route::get('savingtransactions/receipt/{id}', 'SavingtransactionsController@receipt');
Route::get('savingtransactions/statement/{id}', 'SavingtransactionsController@statement');
Route::get('savingtransactions/void/{id}', 'SavingtransactionsController@void');

Route::post('savingtransactions/import', 'SavingtransactionsController@import');

//Route::resource('savingpostings', 'SavingpostingsController');
Route::resource('shares', 'SharesController');
Route::post('shares/update/{id}', 'SharesController@update');
Route::get('shares/delete/{id}', 'SharesController@destroy');
Route::get('shares/edit/{id}', 'SharesController@edit');
Route::get('shares/show/{id}', 'SharesController@show');

Route::get('sharetransactions/show/{id}', 'SharetransactionsController@show');
Route::resource('sharetransactions', 'SharetransactionsController');
Route::get('sharetransactions/create/{id}', 'SharetransactionsController@create');

Route::post('license/key', 'OrganizationsController@generate_license_key');
Route::post('license/activate', 'OrganizationsController@activate_license');
Route::get('license/activate/{id}', 'OrganizationsController@activate_license_form');

Route::resource('loanproducts', 'LoanproductsController');
Route::post('loanproducts/update/{id}', 'LoanproductsController@update');
Route::get('loanproducts/delete/{id}', 'LoanproductsController@destroy');
Route::get('loanproducts/edit/{id}', 'LoanproductsController@edit');
Route::get('loanproducts/show/{id}', 'LoanproductsController@show');
Route::get('memberloanshow/{id}', 'LoanproductsController@memberloanshow');

Route::resource('loanguarantors', 'LoanguarantorsController');
Route::post('loanguarantors/update/{id}', 'LoanguarantorsController@update');
Route::get('loanguarantors/delete/{id}', 'LoanguarantorsController@destroy');
Route::get('loanguarantors/edit/{id}', 'LoanguarantorsController@edit');
Route::get('loanguarantors/create/{id}', 'LoanguarantorsController@create');
Route::get('memberloanguarantors/create/{id}', 'LoanguarantorsController@create');
Route::get('loanguarantors/css/{id}', 'LoanguarantorsController@create');

Route::post('loanguarantors/cssupdate/{id}', 'LoanguarantorsController@cssupdate');
Route::get('loanguarantors/cssdelete/{id}', 'LoanguarantorsController@cssdestroy');
Route::get('loanguarantors/cssedit/{id}', 'LoanguarantorsController@cssedit');

Route::resource('loans', 'LoanaccountsController');
Route::get('loans/apply/{id}', 'LoanaccountsController@apply');
Route::get('guarantorapproval', 'LoanaccountsController@guarantor');
Route::post('loans/apply', 'LoanaccountsController@doapply');
Route::post('loans/application', 'LoanaccountsController@doapply2');


Route::get('loantransactions/statement/{id}', 'LoantransactionsController@statement');
Route::get('loantransactions/receipt/{id}', 'LoantransactionsController@receipt');

Route::get('loans/application/{id}', 'LoanaccountsController@apply2');
Route::post('shopapplication', 'LoanaccountsController@shopapplication');

Route::get('loans/edit/{id}', 'LoanaccountsController@edit');
Route::post('loans/update/{id}', 'LoanaccountsController@update');

Route::get('loans/approve/{id}', 'LoanaccountsController@approve');
Route::post('loans/approve/{id}', 'LoanaccountsController@doapprove');
Route::post('gurantorapprove/{id}', 'LoanaccountsController@guarantorapprove');
Route::post('gurantorreject/{id}', 'LoanaccountsController@guarantorreject');

Route::get('loans/reject/{id}', 'LoanaccountsController@reject');
Route::post('rejectapplication', 'LoanaccountsController@rejectapplication');

Route::get('loans/disburse/{id}', 'LoanaccountsController@disburse');
Route::post('loans/disburse/{id}', 'LoanaccountsController@dodisburse');

Route::get('loans/show/{id}', 'LoanaccountsController@show');
Route::get('memberloan/show/{id}', 'LoanaccountsController@show');

Route::post('loans/amend/{id}', 'LoanaccountsController@amend');

Route::get('loans/reject/{id}', 'LoanaccountsController@reject');
Route::post('loans/reject/{id}', 'LoanaccountsController@rejectapplication');


Route::get('loanaccounts/topup/{id}', 'LoanaccountsController@gettopup');
Route::post('loanaccounts/topup/{id}', 'LoanaccountsController@topup');
Route::get('loans/topupReport/{id}', 'LoanaccountsController@topupReport');

Route::get('memloans/{id}', 'LoanaccountsController@show2');

Route::resource('loanrepayments', 'LoanrepaymentsController');

Route::get('loanrepayments/create/{id}', 'LoanrepaymentsController@create');
Route::get('memberloanrepayments/create/{id}', 'LoanrepaymentsController@create');
Route::get('loanrepayments/offset/{id}', 'LoanrepaymentsController@offset');
Route::get('memberloanrepayments/offset/{id}', 'LoanrepaymentsController@offset');
Route::post('loanrepayments/offsetloan', 'LoanrepaymentsController@offsetloan');

Route::get('reports', function(){
    return View::make('members.reports');
});

Route::get('reports/member/petrol_statement', function(){
    $members = Member::all();
    return View::make('members.petrol_statement', compact('members'));
});

Route::post('reports/member/petrol_statement',function(){
        $from = Input::get('from');
        $to = Input::get('to');
        $member = Member::where('id',Input::get('member_id'))->get()->first();
        $shares_account = Shareaccount::where('member_id',Input::get('member_id'))->get()->first();
        $transactions = Sharetransaction::where('shareaccount_id','=',$shares_account->id)->where('type','=','credit')
        ->where('pay_for','=','petrol')->whereBetween('date', array($from, $to))->get();
        $petrol_amount = Sharetransaction::where('shareaccount_id','=',$shares_account->id)->where('type','=','credit')
        ->where('pay_for','=','petrol')->whereBetween('date', array($from, $to))->sum('amount');
        $organization = Organization::find(1);
        $pdf = PDF::loadView('pdf.petrol_statement', compact('petrol_amount','transactions','from','to','shares_account', 'organization', 'member'))
        ->setPaper('a6')->setOrientation('potrait');
        return $pdf->stream($member->membership_number.'_'.$member->name.'_petrol_station_investment_statement.pdf');
});

Route::get('reports/combined', function(){
    $members = Member::all();
    return View::make('members.combined', compact('members'));
});


Route::get('loanreports', function(){
    $loanproducts = Loanproduct::all();
    return View::make('loanaccounts.reports', compact('loanproducts'));
});


Route::get('savingreports', function(){
    $savingproducts = Savingproduct::all();
    return View::make('savingaccounts.reports', compact('savingproducts'));
});


Route::get('financialreports', function(){
    return View::make('pdf.financials.reports');
});


Route::get('officereports', function(){
    $members = Member::all();
    return View::make('pdf.officereportmember',compact('members'));
});

Route::post('reports/officecontribution', function(){
            $organization = Organization::find(1);
            $from = Input::get('from');
            $desc = Input::get('desc');
            $member = Member::find(Input::get('member_id'));
            $members = Member::all();
            $to = Input::get('to');
            if(Input::get('member_id') == 'all'){
                $contributions = Contribution::where('is_void',false)->whereBetween('date', array($from, $to))->groupBy('member_id')->get();
                $missed = Contribution::where('is_void',false)->whereBetween('date', array($from, $to))->where('type','credit')->groupBy('member_id')->get();
                $paid = Contribution::where('is_void',false)->whereBetween('date', array($from, $to))->where('type','debit')->groupBy('member_id')->get();
                $pdf = PDF::loadView('pdf.officeallcontreport', compact('desc','missed','paid','members','contributions','from','to', 'organization'))->setPaper('a4')->setOrientation('potrait');
                return $pdf->stream('Contribution.pdf');
            }else{
                $missed = Contribution::where('member_id',Input::get('member_id'))->where('is_void',false)->whereBetween('date', array($from, $to))->where('type','credit')->get();
                $paid = Contribution::where('member_id',Input::get('member_id'))->where('is_void',false)->whereBetween('date', array($from, $to))->where('type','debit')->get();
                $contributions = Contribution::where('member_id',Input::get('member_id'))->where('is_void',false)->whereBetween('date', array($from, $to))->get();
                $pdf = PDF::loadView('pdf.officecontreport', compact('desc','paid','missed','member','contributions','from','to', 'organization'))->setPaper('a4')->setOrientation('potrait');
                return $pdf->stream('Contribution.pdf');
          }
});

Route::get('expenseincomereports', function(){
    $vehicles = Vehicle::all();
    return View::make('pdf.expenditureselect',compact('vehicles'));
});

Route::get('reports/listing', 'ReportsController@members');
Route::get('reports/savingperiod', 'ReportsController@savingperiod');
Route::get('reports/savingproductperiod', 'ReportsController@savingproductperiod');
Route::get('reports/remittance', 'ReportsController@remittance');
Route::get('reports/blank', 'ReportsController@template');
Route::get('reports/loanlisting', 'ReportsController@loanlisting');

Route::get('reports/loanproduct/{id}', 'ReportsController@loanproduct');

Route::post('reports/savinglisting', 'ReportsController@savinglisting');

Route::post('reports/savingproduct', 'ReportsController@savingproduct');

Route::post('reports/financials', 'ReportsController@financials');
Route::post('reports/expenseincome', 'ReportsController@expenseincome');
Route::post('reports/tbl', 'ReportsController@tlbrep');
Route::get('reports/member/payroll', 'ReportsController@payrollmembers');
Route::post('reports/payroll', 'ReportsController@payroll');
Route::get('reports/members/personalStatement', 'ReportsController@personalStatement');
Route::post('reports/members/personalStatement', 'ReportsController@combinedStatement');
Route::get('portal', function(){
    $members = DB::table('members')->where('is_active', '=', TRUE)->get();
    return View::make('css.members', compact('members'));
});
Route::get('portal/activate/{id}', 'MembersController@activateportal');
Route::get('portal/deactivate/{id}', 'MembersController@deactivateportal');
Route::get('css/reset/{id}', 'MembersController@reset');
/*
* Vendor controllers
*/
Route::resource('vendors', 'VendorsController');
Route::get('vendors/create', 'VendorsController@create');
Route::post('vendors/update/{id}', 'VendorsController@update');
Route::get('vendors/edit/{id}', 'VendorsController@edit');
Route::get('vendors/delete/{id}', 'VendorsController@destroy');
Route::get('vendors/products/{id}', 'VendorsController@products');
Route::get('vendors/orders/{id}', 'VendorsController@orders');

/*
* products controllers
*/
Route::resource('products', 'ProductsController');
Route::post('products/update/{id}', 'ProductsController@update');
Route::get('products/edit/{id}', 'ProductsController@edit');
Route::get('products/create', 'ProductsController@create');
Route::get('products/delete/{id}', 'ProductsController@destroy');
Route::get('products/orders/{id}', 'ProductsController@orders');
Route::get('shop', 'ProductsController@shop');



Route::resource('contributions', 'ContributionsController');
Route::get('contributions/create/{id}', 'ContributionsController@create');
Route::get('contributions/void/{id}', 'ContributionsController@destroy');
Route::get('member/contributions/{id}', 'ContributionsController@show');
Route::get('contributions/statement/{id}', 'ContributionsController@statement');

/*
* orders controllers
*/
Route::resource('orders', 'OrdersController');
Route::post('orders/update/{id}', 'OrdersControler@update');
Route::get('orders/edit/{id}', 'OrdersControler@edit');
Route::get('orders/delete/{id}', 'OrdersControler@destroy');




Route::get('savings', function(){

    $mem = Confide::user()->username;



    $memb = DB::table('members')->where('membership_no', '=', $mem)->pluck('id');

    $member = Member::find($memb);




    return View::make('css.savingaccounts', compact('member'));
});


Route::post('loanguarantors', function(){


    $mem_id = Input::get('member_id');

        $member = Member::findOrFail($mem_id);

        $loanaccount = Loanaccount::findOrFail(Input::get('loanaccount_id'));


        $guarantor = new Loanguarantor;

        $guarantor->member()->associate($member);
        $guarantor->loanaccount()->associate($loanaccount);
        $guarantor->amount = Input::get('amount');
        $guarantor->save();



        return Redirect::to('memloans/'.$loanaccount->id);

});


Route::resource('audits', 'AuditsController');

Route::get('backups', function(){


    //$backups = Backup::getRestorationFiles('../app/storage/backup/');

    return View::make('backup');

});


Route::get('backups/create', function(){

    echo '<pre>';

    $instance = Backup::getBackupEngineInstance();

    print_r($instance);

    //Backup::setPath(public_path().'/backups/');

   //Backup::export();
    //$backups = Backup::getRestorationFiles('../app/storage/backup/');

    //return View::make('backup');

});


Route::get('memtransactions/{id}', 'MembersController@savingtransactions');

Route::get('convert', function(){




// get the name of the organization from the database
//$org_id = Confide::user()->organization_id;

$organization = Organization::findorfail(1);



$string =  $organization->name;

echo "Organization: ". $string."<br>";


$organization = new Organization;






$license_code = $organization->encode($string);

echo "License Code: ".$license_code."<br>";


$name2 = $organization->decode($license_code, 7);

echo "Decoded L code: ".$name2."<br>";





$license_key = $organization->license_key_generator($license_code);

echo "License Key: ".$license_key."<br>";

echo "__________________________________________________<br>";

$name4 = $organization->license_key_validator($license_key,$license_code,$string);

echo "Decoded L code: ".$name4."<br>";



});


Route::get('perms', function(){

    $perm = new Permission;

    $perm->name = 'edit_loan_product';
    $perm->display_name = 'edit loan products';
    $perm->category = 'Loanproduct';
    $perm->save();



    $perm = new Permission;

    $perm->name = 'view_loan_product';
    $perm->display_name = 'view loan products';
    $perm->category = 'Loanproduct';
    $perm->save();

    $perm = new Permission;

    $perm->name = 'delete_loan_product';
    $perm->display_name = 'delete loan products';
    $perm->category = 'Loanproduct';
    $perm->save();


    $perm = new Permission;

    $perm->name = 'create_loan_account';
    $perm->display_name = 'create loan account';
    $perm->category = 'Loanaccount';
    $perm->save();


    $perm = new Permission;

    $perm->name = 'view_loan_account';
    $perm->display_name = 'view loan account';
    $perm->category = 'Loanaccount';
    $perm->save();


    $perm = new Permission;

    $perm->name = 'approve_loan_account';
    $perm->display_name = 'approve loan';
    $perm->category = 'Loanaccount';
    $perm->save();


    $perm = new Permission;

    $perm->name = 'disburse_loan';
    $perm->display_name = 'disburse loan';
    $perm->category = 'Loanaccount';
    $perm->save();



});


Route::get('rproducts', function(){

    Product::getRemoteProducts();


});



Route::get('reports/deduction', function(){

   return View::make('deduction');

});

Route::get('reports/members', function(){
   $members  = Member::all();
   return View::make('pdf.memreport',compact('members'));

});

Route::post('report/memberstatements', 'ReportsController@memberstatements');


Route::post('deductions', function(){

    $date = Input::get('date');

    $members = Member::all();

    $loanproducts = Loanproduct::all();

    $savingproducts = Savingproduct::all();

    return View::make('dedreport', compact('members', 'loanproducts', 'savingproducts', 'date'));
});


Route::post('import/savings', function(){

   if(Input::hasFile('savings')){

      $destination = storage_path().'/backup/';

      $filename = str_random(12);

      $ext = Input::file('savings')->getClientOriginalExtension();
      $file = $filename.'.'.$ext;

      Input::file('savings')->move($destination, $file);


    Excel::load(storage_path().'/backup/'.$file, function($reader){

          $results = $reader->get();

        // Getting all results
        foreach($results as $result){

            $date = date('Y-m-d', strtotime($result->date));
            $savingaccount = Member::getMemberAccount($result->id);

            if(Savingtransaction::trasactionExists($date, $savingaccount) == false){


                     $amount = $result->amount;
            if($amount >= 0){
                $type = 'credit';
                $description = 'savings deposit';
            } else {
                $type = 'debit';
                $description = 'savings withdrawal';
                $amount = preg_replace('/[^0-9]+/', '', $amount);
            }
            $transacted_by = $result->member;



            Savingtransaction::transact($date, $savingaccount, $amount, $type, $description, $transacted_by);



            }


        }

    });





    return Redirect::back()->with('notice', 'savings have been imported');

} else {

    return Redirect::back()->with('error', 'You have not uploaded any file');

}



});

Route::get('api/dropdown', function(){
    $id = Input::get('option');
    $bbranch = Bank::find($id)->bankbranch;
    return $bbranch->lists('bank_branch_name', 'id');
});

Route::get('api/loanaccount', function(){
    $id = Input::get('option');
    $vehicle = Vehicle::find($id);
    $loanproduct = DB::table('loanaccounts')
                 ->join('loanproducts', 'loanaccounts.loanproduct_id', '=', 'loanproducts.id')
                 ->where('member_id',$vehicle->member_id)
                 ->where('is_approved',1)
                 ->where('is_rejected',0)
                 ->select('loanaccounts.id as id','loanproducts.name as name')
                 ->lists('name','id');

    //$loanproduct = Loanaccount::where('member_id',$vehicle->member_id)->loanproduct;
    return $loanproduct;
});

Route::get('api/membership_status',function(){
    $id = Input::get('option');
    $vehicle = Vehicle::find($id);
    /*Get The member*/
    $member= Member::where('id','=',$vehicle->member_id)->get()->first();
    $shareaccount= Shareaccount::where('member_id','=',$vehicle->member_id)->get()->first();

    $transactions= Sharetransaction::where('shareaccount_id','=',$shareaccount->id)
        ->where('type','=','credit')->where('pay_for','=','membership')->sum('amount');
    if($transactions >= $member->total_membership){
        return $status='paid';
    }
});

Route::get('api/share_status',function(){
    $id = Input::get('option');
    $vehicle = Vehicle::find($id);
    /*Get The member*/
    $member= Member::where('id','=',$vehicle->member_id)->get()->first();
    $shareaccount= Shareaccount::where('member_id','=',$vehicle->member_id)->get()->first();

    $transactions= Sharetransaction::where('shareaccount_id','=',$shareaccount->id)
        ->where('type','=','credit')->where('pay_for','=','shares')->sum('amount');

    if($transactions >= $member->total_shares){
        return $status='paid';
    }
});

Route::post('import/loans', function(){


     if(Input::hasFile('loans')){

      $destination = storage_path().'/backup/';

      $filename = str_random(12);

      $ext = Input::file('loans')->getClientOriginalExtension();
      $file = $filename.'.'.$ext;

      Input::file('loans')->move($destination, $file);

      Excel::load(storage_path().'/backup/'.$file, function($reader){

            $results = $reader->get();

            // Getting all results
            foreach($results as $result){


        $date = date('Y-m-d', strtotime($result->date));

        $member_id = $result->id;
        $loanproduct_id = $result->product;

        $amount = $result->amount;

        $member = Member::findorfail($member_id);

        $loanproduct = Loanproduct::findorfail($loanproduct_id);

        $loanaccount = new Loanaccount;
        $loanaccount->member()->associate($member);
        $loanaccount->loanproduct()->associate($loanproduct);
        $loanaccount->application_date = $date;
        $loanaccount->amount_applied = $amount;
        $loanaccount->repayment_duration = $result->period;


        $loanaccount->date_approved = $date;
        $loanaccount->amount_approved = $amount;
        $loanaccount->interest_rate = $result->rate;
        $loanaccount->period = $result->period;
        $loanaccount->is_approved = TRUE;
        $loanaccount->is_new_application = FALSE;

        $loanaccount->date_disbursed = $date;
        $loanaccount->amount_disbursed = $amount;
        $loanaccount->repayment_start_date = $date;
        $loanaccount->account_number = Loanaccount::loanAccountNumber($loanaccount);
        $loanaccount->is_disbursed = TRUE;


        $loanaccount->save();

        $loanamount = $amount + Loanaccount::getInterestAmount($loanaccount);
        Loantransaction::disburseLoan($loanaccount, $loanamount, $date);

            }

    });

  }


});

Route::get('mpesa',function(){

});

Route::get('api/label', function(){
    $id = Input::get('option');
    $currency = Currency::find(1);
    $loanproduct = Loanproduct::find($id);
    return $currency->shortname.'. '.number_format($loanproduct->auto_loan_limit,2);
});

Route::get('api/getVehicle', function(){
    $id = Input::get('option');
    $total = DB::table('sharetransactions')
            ->join('shareaccounts', 'sharetransactions.shareaccount_id', '=', 'shareaccounts.id')
            ->where('member_id' ,'=', $id)
            ->sum('amount');
    //$total = Vehicleincome::where('member_id',$id)->where('type','Income')->sum('amount');
    $loanpaid = DB::table('loantransactions')
            ->join('loanaccounts', 'loantransactions.loanaccount_id', '=', 'loanaccounts.id')
            ->where('member_id', $id)
            ->where('payment_via', 'shares')
            ->sum('loantransactions.amount');
    $savingspaid = DB::table('savingtransactions')
            ->join('savingaccounts', 'savingtransactions.savingaccount_id', '=', 'savingaccounts.id')
            ->where('member_id', $id)
            ->where('payment_via', 'vehicle')
            ->sum('savingtransactions.amount');
    //$paid = Loantransaction::where('payment_via','vehicle')->sum('amount');
    return $total-$loanpaid;
});


Route::get('backup', function(){

  $backup = Artisan::call('db:export');

if($backup == 0){

  return Redirect::back()->with('notice', 'database has been successfully backed up');
}

});


Route::resource('membersfee', 'MembersFeeController');
Route::get('membersfee/edit/{id}', 'MembersFeeController@edit');
Route::post('membersfee/update/{id}', 'MembersFeeController@update');
Route::get('membersfee/delete/{id}', 'MembersFeeController@destroy');
Route::get('membersfee/create', 'MembersFeeController@create');

Route::resource('tlbpayments', 'TlbPaymentsController');
Route::get('tlbpayments/edit/{id}', 'TlbPaymentsController@edit');
Route::post('tlbpayments/update/{id}', 'TlbPaymentsController@update');
Route::get('tlbpayments/delete/{id}', 'TlbPaymentsController@destroy');
Route::get('tlbpayments/create', 'TlbPaymentsController@create');
