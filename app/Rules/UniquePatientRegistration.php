<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;
use App\Models\HIS\PatientRegistry;
use Carbon\Carbon;

class UniquePatientRegistration implements Rule
{
    protected $patientId;
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct($patientId)
    {
        $this->patientId = $patientId;
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        return !PatientRegistry::where('patient_id', $this->patientId)
        ->whereDate('created_at', Carbon::today())
        ->exists();
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'The patient is already been registered today';
    }
}
