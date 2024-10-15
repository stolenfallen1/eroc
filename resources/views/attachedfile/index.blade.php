@if($type=='doctor')
<center>
    <img src="{{asset('storage/'.$data->doctors_Request_Path)}}" width="60%" />
</center>
@else
<center>
    <img src="{{asset('storage/'.$data->payments->payment_UploadPath)}}" width="60%" />
</center>
@endif