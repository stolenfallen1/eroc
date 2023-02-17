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
    <div class="page-content container-fluid">
        @include('voyager::alerts')
        <div class="row roles">
            <div class="col-md-8">

                <div class="panel panel-bordered">
                    <!-- form start -->
                    <form class="form-edit-add" role="form"
                        action="@if (isset($dataTypeContent->id)) {{ route('voyager.' . $dataType->slug . '.update', $dataTypeContent->id) }}@else{{ route('voyager.' . $dataType->slug . '.store') }} @endif"
                        method="POST" enctype="multipart/form-data">

                        <!-- PUT Method if we are editing -->
                        @if (isset($dataTypeContent->id))
                            {{ method_field('PUT') }}
                        @endif

                        <!-- CSRF TOKEN -->
                        {{ csrf_field() }}

                        <div class="panel-body">

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


                            <label for="permission">{{ __('voyager::generic.permissions') }}</label><br>
                            <a href="#" class="permission-select-all">{{ __('voyager::generic.select_all') }}</a> /
                            <a href="#" class="permission-deselect-all">{{ __('voyager::generic.deselect_all') }}</a>
                            <?php
                            $role_permissions = isset($dataTypeContent) ? $dataTypeContent->permissions->pluck('key')->toArray() : [];
                            ?>
                            <div class="panel-group permissions checkbox" id="accordion">

                                <table class="table">
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
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach (Voyager::model('Permission')->all()->groupBy('table_name') as $table => $permission)
                                           
                                                <tr>
                                                    <td>
                                                       <div class="padding">
                                                            <input type="checkbox" id="{{ $table }}" class="permission-group">
                                                            <label for="{{ $table }}"><strong>{{ \Illuminate\Support\Str::title(str_replace('_', ' ', $table)) }}</strong></label>
                                                       </div>
                                                    </td>
                                                    @foreach ($permission as $perm)
                                                        <td>
                                                            <center> <input type="checkbox"
                                                                    id="permission-{{ $perm->id }}"
                                                                    name="permissions[{{ $perm->id }}]"
                                                                    class="the-permission" value="{{ $perm->id }}"
                                                                    @if (in_array($perm->key, $role_permissions)) checked @endif>
                                                            </center>
                                                        </td>
                                                    @endforeach
                                                </tr>
                                                {{-- <div class="panel panel-default">
                                                <div class="panel-heading">
                                                    <h4 class="panel-title">
                                                        <a data-toggle="collapse"
                                                            data-parent="#accordion{{ $table }}"
                                                            href="#collapse{{ $table }}">
                                                            {{ \Illuminate\Support\Str::title(str_replace('_', ' ', $table)) }}</a>
                                                    </h4>
                                                </div>
                                                <div id="collapse{{ $table }}" class="panel-collapse">
                                                    <div class="container">

                                                        <div class="row">
                                                            <div class="col-lg-4">
                                                                <input type="checkbox" id="{{ $table }}"
                                                                    class="permission-group">
                                                                <label
                                                                    for="{{ $table }}"><strong>{{ \Illuminate\Support\Str::title(str_replace('_', ' ', $table)) }}</strong></label>
                                                            </div>

                                                            @foreach ($permission as $perm)
                                                                <div class="col-lg-1">

                                                                    <input type="checkbox"
                                                                        id="permission-{{ $perm->id }}"
                                                                        name="permissions[{{ $perm->id }}]"
                                                                        class="the-permission" value="{{ $perm->id }}"
                                                                        @if (in_array($perm->key, $role_permissions)) checked @endif>
                                                                    <label
                                                                        for="permission-{{ $perm->id }}">{{ substr($perm->key, 0, strpos($perm->key, '_')) }}</label>
                                                                </div>
                                                            @endforeach

                                                        </div>
                                                    </div>

                                                </div>
                                            </div> --}}
                                    </tbody>
                                    @endforeach
                                </table>
                            </div>
                        </div><!-- panel-body -->
                        <div class="panel-footer">
                            <button type="submit" class="btn btn-primary">{{ __('voyager::generic.submit') }}</button>
                        </div>
                    </form>

                    <div style="display:none">
                        <input type="hidden" id="upload_url" value="{{ route('voyager.upload') }}">
                        <input type="hidden" id="upload_type_slug" value="{{ $dataType->slug }}">
                    </div>
                </div>
            </div>
        </div>
    </div>
@stop

@section('javascript')
    <script>
        $('document').ready(function() {
            $('.toggleswitch').bootstrapToggle();

            $('.permission-group').on('change', function() {
                $(this).parent('div').parent('td').siblings('td').find("input[type='checkbox']").prop('checked', this
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
                    $(this).parent('div').parent('td').siblings('td').find("input[type='checkbox']").each(function() {
                        if (!this.checked) allChecked = false;
                    });
                    $(this).prop('checked', allChecked);
                });
            }

            parentChecked();

            $('.the-permission').on('change', function() {
                parentChecked();
            });
        });
    </script>
@stop
