@extends('voyager::master')

@section('page_title', __('voyager::generic.' . (isset($dataTypeContent->id) ? 'edit' : 'add')) . ' ' .
    $dataType->getTranslatedAttribute('display_name_singular'))

@section('css')
    <meta name="csrf-token" content="{{ csrf_token() }}">
@stop

@section('page_header')
    <h1 class="page-title">
        <i class="{{ $dataType->icon }}"></i>
        {{ __('voyager::generic.' . (isset($dataTypeContent->id) ? 'edit' : 'add')) . ' ' . $dataType->getTranslatedAttribute('display_name_singular') }}
    </h1>
@stop

@section('content')
    <div class="modal modal-info fade" tabindex="-1" id="table_info" role="dialog" data-backdrop="static" data-keyboard="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <a href="{{ route('voyager.roles.index')}}"  class="close" ><i class="voyager-close"></i>  <span aria-hidden="true">&times;</span></a>
                    
                    <h4 class="modal-title"><i class="voyager-list"></i> System User Modules</h4>
                </div>
                <form class="form-edit-add" role="form"
                    action="@if (isset($dataTypeContent->id)) {{ route('voyager.' . $dataType->slug . '.update', $dataTypeContent->id) }}@else{{ route('voyager.' . $dataType->slug . '.store') }} @endif"
                    method="POST" enctype="multipart/form-data">
                    <div class="modal-body">


                        <!-- PUT Method if we are editing -->
                        @if (isset($dataTypeContent->id))
                            {{ method_field('PUT') }}
                        @endif

                        <!-- CSRF TOKEN -->
                        {{ csrf_field() }}

                        @if (count($errors) > 0)
                            <div class="alert alert-danger">
                                <ul>
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        @foreach ($dataType->addRows as $row)
                            <div class="col-lg-6">
                                <div class="form-group">
                                    <label for="name">{{ $row->getTranslatedAttribute('display_name') }}</label>
                                    {!! Voyager::formField($row, $dataType, $dataTypeContent) !!}
                                </div>
                            </div>
                        @endforeach
                      
                              
                        <?php
                        $role_permissions = isset($dataTypeContent) ? $dataTypeContent->permissions->pluck('key')->toArray() : [];
                        ?>
                           <a href="#" class="permission-select-all">{{ __('voyager::generic.select_all') }}</a> /
                           <a href="#" class="permission-deselect-all">{{ __('voyager::generic.deselect_all') }}</a>
                      
                        <div class="panel-group permissions checkbox" >
                            <div class="accordion" id="accordionExample" style="height: 350px;overflow-x: auto;">
                                @foreach (App\Models\Database\Database::with('permissions')->get()->groupBy('driver') as $modules => $module)
                                    @php
                                        $databaseconnection = App\Models\Database\Database::where('driver', $modules)->first();
                                    @endphp
                                     
                                    <div class="card">
                                        <div class="card-header" id="headingOne{{ $modules }}">
                                            <h4 class="mb-0">
                                                <label data-toggle="collapse" data-target="#collapseOne{{ $modules }}"
                                                    aria-expanded="true"
                                                    aria-controls="collapseOne{{ $modules }}"><strong><i  class="voyager-data"></i > {{ \Illuminate\Support\Str::title(str_replace('_', ' ', $databaseconnection->description)) }}</strong></label>
                                            </h4>
                                        </div>

                                        <div id="collapseOne{{ $modules }}" class="collapse"
                                            aria-labelledby="headingOne{{ $modules }}"
                                            data-parent="#accordionExample" >
                                            <div >
                                                <div class="form-group">
                                                    <input  type="text" placeholder="Search.." class="form-control myInput" attrdriver="{{$modules}}" >
                                                </div>
                                            </div>
                                            <div >
                                                <table class="table" id="{{$modules}}">
                                                    <thead>
                                                        <tr>
                                                            <th>Module</th>
                                                            <th class=" text-center">Browse</th>
                                                            <th class=" text-center">Read</th>
                                                            <th class=" text-center">Edit</th>
                                                            <th class=" text-center">Add</th>
                                                            <th class=" text-center">Delete</th>
                                                            <th class=" text-center">Print</th>
                                                            <th class=" text-center">Post</th>
                                                            <th class=" text-center">Approved</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach (Voyager::model('Permission')->all()->where('driver', $modules)->groupBy('table_name') as $table => $permission)
                                                            <tr>
                                                                <td>
                                                                    <div class="padding">
                                                                        <input type="checkbox" id="{{ $table }}"
                                                                            class="permission-group">
                                                                        <label
                                                                            for="{{ $table }}"><strong>{{ \Illuminate\Support\Str::title(str_replace('_', ' ', $table)) }}</strong></label>
                                                                    </div>
                                                                </td>
                                                                @foreach ($permission as $perm)

                                                                    <td>
                                                                        <center> <input type="checkbox"
                                                                                id="permission-{{ $perm->id }}"
                                                                                name="permissions[{{ $perm->id }}]"
                                                                                class="the-permission"
                                                                                value="{{ $perm->id }}"
                                                                                @if (in_array($perm->key, $role_permissions)) checked @endif>
                                                                        </center>
                                                                    </td>
                                                                @endforeach
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                       
                    </div>
                    <div class="modal-footer">
                        <div style="display:none">
                            <input type="hidden" id="upload_url" value="{{ route('voyager.upload') }}">
                            <input type="hidden" id="upload_type_slug" value="{{ $dataType->slug }}">
                        </div>
                        <button type="submit" class="btn btn-primary">{{ __('voyager::generic.submit') }}</button>
                </form>
            </div>
        </div><!-- /.modal-content -->
    </div><!-- /.modal-dialog -->
    </div><!-- /.modal -->

    <div class="page-content container-fluid">
        @include('voyager::alerts')
        <div class="row roles">
            <div class="col-md-8">

                <div class="panel panel-bordered">
                    <!-- form start -->

                </div>
            </div>
        </div>
    </div>
@stop

@section('javascript')
    <script>
        $('document').ready(function() {
            $('#table_info').modal('show');
            $('.toggleswitch').bootstrapToggle();

            $('.permission-group').on('change', function() {
                $(this).parent('div').parent('td').siblings('td').find("input[type='checkbox']").prop(
                    'checked', this
                    .checked);
            });

            $('.permission-select-all').on('click', function() {
                $('div.permissions').find("input[type='checkbox']").prop('checked', true);
                return false;
            });

            $('.permission-deselect-all').on('click', function() {
                $('div.permissions').find("table input[type='checkbox']").prop('checked', false);
                return false;
            });

            function parentChecked() {
                $('.permission-group').each(function() {
                    var allChecked = true;
                    $(this).parent('div').parent('td').siblings('td').find("input[type='checkbox']").each(
                        function() {
                            if (!this.checked) allChecked = false;
                        });
                    $(this).prop('checked', allChecked);
                });
            }
            $("body").on("keyup",'.myInput', function() {
                    var value = $(this).val().toLowerCase();
                    var driver = $(this).attr('attrdriver');
                    $("#"+driver+" tr").filter(function() {
                        $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
                    });
                });
            parentChecked();

            $('.the-permission').on('change', function() {
                parentChecked();
            });
        });
    </script>
@stop
