@if($type=='doctor')
<img src="{{asset('storage/'.$data->doctors_Request_Path)}}" />
@else
<img src="{{asset('storage/'.$data->payments->payment_UploadPath)}}" />
@endif