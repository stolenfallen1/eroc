@extends('voyager::master')

@section('page_title', __('voyager::generic.' . (isset($dataTypeContent->id) ? 'edit' : 'add')) . ' ' .
    $dataType->getTranslatedAttribute('display_name_singular'))

@section('css')
    <meta name="csrf-token" content="{{ csrf_token() }}">
@stop

@section('page_header')
    <h1 class="page-title">
        <i class="voyager-laptop"></i>
        System User Details
    </h1>
@stop

@section('content')
    <div class="page-content container-fluid">
        <div class="modal modal-info fade" tabindex="-1" id="table_info" role="dialog" data-backdrop="static"
            data-keyboard="true">
            <div class="modal-dialog modal-lg usermanager">
                <div class="modal-content">
                    <div class="modal-header">
                        <a href="{{ route('voyager.users.index') }}" class="close"><i class="voyager-close"></i> <span
                                aria-hidden="true">&times;</span></a>

                        <h4 class="modal-title"><i class="voyager-laptop"></i> System User Details</h4>
                    </div>
                    <form class="form-edit-add" role="form"
                        action="@if (!is_null($dataTypeContent->getKey())) {{ route('voyager.' . $dataType->slug . '.update', $dataTypeContent->getKey()) }}@else{{ route('voyager.' . $dataType->slug . '.store') }} @endif"
                        method="POST" enctype="multipart/form-data" autocomplete="off">
                        <!-- PUT Method if we are editing -->
                        @if (isset($dataTypeContent->id))
                            {{ method_field('PUT') }}
                        @endif
                        {{ csrf_field() }}

                        <div class="modal-body" style="padding: 0px;">
                            <ul class="nav nav-tabs">
                                <li class="active"><a data-toggle="tab" href="#General">General</a></li>
                                <li><a data-toggle="tab" href="#Miscellanous">Miscellanous</a></li>
                            </ul>

                            <div class="tab-content">
                                <div id="General" class="tab-pane fade in active">
                                    <div class="row">
                                        <div class="col-md-7">
                                            <div>
                                                {{-- <div class="panel"> --}}
                                                @if (count($errors) > 0)
                                                    <div class="alert alert-danger">
                                                        <ul>
                                                            @foreach ($errors->all() as $error)
                                                                <li>{{ $error }}</li>
                                                            @endforeach
                                                        </ul>
                                                    </div>
                                                @endif

                                                <div class="panel-body">
                                                    <table class="table table-condensed">
                                                        <tr>
                                                            <th> <label for="lastname">{{ __('Last Name') }}</label></th>
                                                            <td>
                                                                <input type="text" class="form-control onkeyuplastname"
                                                                    id="lastname" name="lastname"
                                                                    placeholder="{{ __('Last Name') }}"
                                                                    value="{{ old('lastname', $dataTypeContent->lastname ?? '') }}">
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <th><label for="firstname">{{ __('First Name') }}</label></th>
                                                            <td>
                                                                <input type="text" class="form-control onkeyupfirstname"
                                                                    id="firstname" name="firstname"
                                                                    placeholder="{{ __('First Name') }}"
                                                                    value="{{ old('firstname', $dataTypeContent->firstname ?? '') }}">
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <th> <label for="name">{{ __('Middle Name') }}</label></th>
                                                            <td>
                                                                <input type="text" class="form-control onkeyupmiddlename"
                                                                    id="middlename" name="middlename"
                                                                    placeholder="{{ __('Middle Name') }}"
                                                                    value="{{ old('middlename', $dataTypeContent->middlename ?? '') }}">
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <th> <label for="name">{{ __('Custom Name') }}</label></th>
                                                            <td>
                                                                <input type="text" class="form-control" id="name"
                                                                    readonly name="name"
                                                                    placeholder="{{ __('Custom Name') }}"
                                                                    value="{{ old('name', $dataTypeContent->name ?? '') }}">
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <th> <label for="birthdate">{{ __('Birth Date') }}</label></th>
                                                            <td>
                                                                <input type="date" class="form-control" id="birthdate"
                                                                    name="birthdate" placeholder="{{ __('Birth Date') }}"
                                                                    value="{{ old('birthdate', date('Y-m-d', strtotime($dataTypeContent->birthdate)) ?? '') }}">
                                                            </td>
                                                        </tr>

                                                        <tr>
                                                            <th>
                                                                <label for="mobileno">{{ __('Mobile No.') }}</label>
                                                            </th>
                                                            <td>
                                                                <input type="text" class="form-control" id="mobileno"
                                                                    name="mobileno" placeholder="{{ __('Mobile No.') }}"
                                                                    value="{{ old('mobileno', $dataTypeContent->mobileno ?? '') }}">
                                                            </td>
                                                        </tr>

                                                        <tr>
                                                            <th>
                                                                <label
                                                                    for="email">{{ __('voyager::generic.email') }}</label>
                                                            </th>
                                                            <td>
                                                                <input type="email" class="form-control" id="email"
                                                                    name="email"
                                                                    placeholder="{{ __('voyager::generic.email') }}"
                                                                    value="{{ old('email', $dataTypeContent->email ?? '') }}">
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <th>

                                                            </th>
                                                            <td>
                                                                <br>
                                                            </td>
                                                        </tr>

                                                        @can('editRoles', $dataTypeContent)
                                                            <tr>
                                                                <th>
                                                                    {{-- <label for="default_role">{{ __('voyager::profile.role_default') }}</label> --}}
                                                                    <label for="default_role">User Group</label>
                                                                    @php
                                                                        $dataTypeRows = $dataType->{isset($dataTypeContent->id) ? 'editRows' : 'addRows'};
                                                                        $row = $dataTypeRows->where('field', 'user_belongsto_role_relationship')->first();
                                                                        $options = $row->details;
                                                                    @endphp

                                                                </th>
                                                                <td>
                                                                    @include('voyager::formfields.relationship')
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <th>
                                                                    {{-- <label for="additional_roles">{{ __('voyager::profile.roles_additional') }}</label> --}}
                                                                    <label for="additional_roles">Position</label>
                                                                    @php
                                                                        $row = $dataTypeRows->where('field', 'user_belongstomany_role_relationship')->first();
                                                                        $options = $row->details;
                                                                    @endphp
                                                                </th>
                                                                <td>
                                                                    @include('voyager::formfields.relationship')
                                                                </td>
                                                            </tr>
                                                        @endcan
                                                        <tr>
                                                            <th>
                                                                <label for="branch">Branch</label>
                                                            </th>
                                                            <td>
                                                                <select class="form-control select2" id="branch"
                                                                    name="branch_id">
                                                                    @foreach (App\Models\BuildFile\Branchs::all() as $branch)
                                                                        <option value="{{ $branch->id }}"
                                                                            {{ $branch->id == $dataTypeContent->id ? 'selected' : '' }}>
                                                                            {{ $branch->name }}</option>
                                                                    @endforeach
                                                                </select>
                                                            </td>
                                                        </tr>


                                                        <tr>
                                                            <th>
                                                                <label for="department">Department</label>
                                                            </th>
                                                            <td>
                                                                <select class="form-control select2" id="department"
                                                                    name="warehouse_id">
                                                                    @foreach (App\Models\BuildFile\Warehouses::all() as $warehouse)
                                                                        <option value="{{ $warehouse->id }}"
                                                                            {{ $warehouse->id == $dataTypeContent->warehouse_id ? 'selected' : '' }}>
                                                                            {{ $warehouse->warehouse_description }}
                                                                        </option>
                                                                    @endforeach
                                                                </select>
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <th>
                                                                <label for="departmentsection">Unit Section</label>
                                                            </th>
                                                            <td>
                                                                <select class="form-control select2"
                                                                    id="departmentsection" name="warehouse_id">
                                                                    @foreach (App\Models\BuildFile\Warehouses::all() as $warehouse)
                                                                        <option value="{{ $warehouse->id }}"
                                                                            {{ $warehouse->id == $dataTypeContent->warehouse_id ? 'selected' : '' }}>
                                                                            {{ $warehouse->warehouse_description }}
                                                                        </option>
                                                                    @endforeach
                                                                </select>
                                                            </td>
                                                        </tr>



                                                        {{-- <tr>
                                                            <th>  
                                                                @php
                                                                    if (isset($dataTypeContent->locale)) {
                                                                        $selected_locale = $dataTypeContent->locale;
                                                                    } else {
                                                                        $selected_locale = config('app.locale', 'en');
                                                                    }
                                                                    
                                                                @endphp
                                                                  <label for="locale">{{ __('voyager::generic.locale') }}</label>
                                                            </th>
                                                            <td> 
                                                                <select class="form-control select2" id="locale"
                                                                    name="locale">
                                                                    @foreach (Voyager::getLocales() as $locale)
                                                                        <option value="{{ $locale }}"
                                                                            {{ $locale == $selected_locale ? 'selected' : '' }}>
                                                                            {{ $locale }}
                                                                        </option>
                                                                    @endforeach
                                                                </select>
                                                            </td>
                                                        </tr> --}}


                                                    </table>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-md-5">
                                            <div class="panel-body">
                                              
                                                <div class="form-group  custom-file-upload">
                                                    @if (isset($dataTypeContent->avatar))
                                                        <div class="preview-image" style="width:130px; height:125px; clear:both; display:block; padding:2px;">
                                                            <img src="{{ filter_var($dataTypeContent->avatar, FILTER_VALIDATE_URL) ? $dataTypeContent->avatar : Voyager::image($dataTypeContent->avatar) }}"
                                                                style="width:130px; height:125px; clear:both; display:block; padding:2px; border:1px solid #ddd; margin-bottom:10px;" />
                                                        </div>
                                                    @endif
                                                    <input type="file" data-name="avatar" name="avatar" id="avatar" class="dnone">
                                                </div>
                                                <table class="table table-condensed">
                                                    <tbody>
                                                        <tr>
                                                            <th>
                                                                <label for="idnumber">User ID</label>
                                                            </th>
                                                            <td>
                                                                <input type="text" class="form-control" id="idnumber"
                                                                    name="idnumber" placeholder="Id Number"
                                                                    value="{{ old('idnumber', $dataTypeContent->idnumber ?? '') }}">
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <th>
                                                                <label
                                                                    for="password">{{ __('voyager::generic.password') }}</label>
                                                            </th>
                                                            <td>

                                                                <input type="password" class="form-control" " placeholder="Password" id="password" name="password" value="" autocomplete="new-password">
                                                                     @if (isset($dataTypeContent->password))
                                                                <small>{{ __('voyager::profile.password_hint') }}</small>
                                                                @endif
                                                            </td>

                                                        </tr>

                                                    </tbody>
                                                </table>


                                            </div>
                                        </div>
                                    </div>

                                </div>
                                <div id="Miscellanous" class="tab-pane fade">
                                    <div class="panel-body">
                                        <div class="form-group">
                                            <strong> Administrative Accessibility</strong>
                                        </div>
                                        <hr>
                                        <p>Users who have given an Administrative type of accessibility could access all
                                            modules and reports of the system including System Users Account managment
                                            module. However, the rights to access modules and reports
                                            that has been granted prior to giving the user an administrative type of
                                            accessibility will be preserved and will be available for restoration if in case
                                            the user goes back to non-administrative type of accessibility.
                                        </p>
                                        <div class="padding">
                                            <input type="checkbox" id="grant"
                                                class="permission-group permission-select-all">
                                            <label for="grant"><strong>Grant this User an Administrative
                                                    Access</strong></label>
                                        </div>

                                        <hr>
                                    </div>
                                    <div class="panel-body">
                                        <div class="form-group">
                                            <strong>System Accessibility</strong>
                                        </div>
                                        <hr>
                                        <div class="row">
                                            <?php
                                            $system_useraccess = isset($dataTypeContent)
                                                ? App\Models\BuildFile\Systemuseraccess::where('user_id', $dataTypeContent->id)
                                                    ->get()
                                                    ->pluck('subsystem_id')
                                                    ->toArray()
                                                : [];
                                            ?>

                                            @foreach (App\Models\BuildFile\SysSubSystem::all() as $key => $subsystem)
                                                <div class="col-lg-6">
                                                    <div class="padding system">
                                                        <input type="checkbox" id="{{ $key }}"
                                                            class="permission-group"
                                                            name="subsystem[{{ $subsystem->id }}]" class="the-permission"
                                                            value="{{ $subsystem->id }}"
                                                            @if (in_array($subsystem->id, $system_useraccess)) checked @endif>
                                                        <label
                                                            for="{{ $key }}"><strong>{{ $subsystem->subsystem_description }}</strong></label>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>

                                        <input type="hidden" class="form-control" name="created_at"
                                            value="{{ old('created_at', $dataTypeContent->created_at ?? '') }}">
                                        <hr>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                               
                                <button type="submit" class="btn btn-primary pull-right save">
                                    {{ __('voyager::generic.save') }}
                                </button>
                            </div>
                    </form>
                    <div style="display:none">
                        <input type="hidden" id="upload_url" value="{{ route('voyager.upload') }}">
                        <input type="hidden" id="upload_type_slug" value="{{ $dataType->slug }}">
                    </div>
                </div>
            </div>
        </div><!-- /.modal-content -->
    </div><!-- /.modal-dialog -->
    </div><!-- /.modal -->
    </div>
@stop

@section('javascript')
    <script>
        $('document').ready(function() {
            $('#table_info').modal('show');
            $('.toggleswitch').bootstrapToggle();
        });
        $('.permission-select-all').on('click', function() {
            if ($(this).is(':checked')) {
                $('div.system').find("input[type='checkbox']").prop('checked', true);
                $('div.system').find("input[type='checkbox']").prop('disabled', true);
            } else {
                $('div.system').find("input[type='checkbox']").prop('checked', false);
                $('div.system').find("input[type='checkbox']").prop('disabled', false);
            }
        });

        $('body').on('keyup', '.onkeyuplastname', function() {
            var lastname = $(this).val();
            $("#name").val(lastname);
        });

        $('body').on('keyup', '.onkeyupfirstname', function() {
            var lastname = $(".onkeyuplastname").val();
            var firstname = $(this).val();
            $("#name").val(lastname + ', ' + firstname + '');
        });

        $('body').on('keyup', '.onkeyupmiddlename', function() {
            var lastname = $(".onkeyuplastname").val();
            var firstname = $('.onkeyupfirstname').val();
            var middlename = $(this).val();
            $("#name").val(lastname + ', ' + firstname + ' ' + middlename);
        });
        $('.permission-deselect-all').on('click', function() {
            $('div.system').find("input[type='checkbox']").prop('checked', false);
            return false;
        });

        function readURL(input) {
            if (input.files && input.files[0]) {
                var reader = new FileReader();
                reader.onload = function(e) {
                    $('#imagePreview').css('background-image', 'url(' + e.target.result + ')');
                    $('#imagePreview').hide();
                    $('#imagePreview').fadeIn(650);
                }
                reader.readAsDataURL(input.files[0]);
            }
        }

        $("body").on('click', '.custom-file-upload', function() {
            // Get the label element and image preview container
            const label = document.querySelector('.custom-file-upload');
            const preview = document.querySelector('.preview-image img');
            // Listen for clicks on the label element
            // Listen for changes in the file input field
            const input = document.getElementById('avatar');
            input.click();
            input.addEventListener('change', function() {
                // Get the selected file
                const file = this.files[0];
                const filename = this.files[0].name;
                // Create a FileReader object
                const reader = new FileReader();
                // Set the image source to the selected file
                reader.addEventListener('load', function() {
                    preview.src = reader.result;
                });
                if (file) {
                    // Read the file as a data URL
                    reader.readAsDataURL(file);
                }
            });
        });
    </script>
@stop
