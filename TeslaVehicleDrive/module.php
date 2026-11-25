<?php

declare(strict_types=1);
require_once __DIR__ . '/../libs/TeslaHelper.php';
require_once __DIR__ . '/../libs/vendor/SymconModulHelper/VariableProfileHelper.php';

class TeslaVehicleDrive extends IPSModuleStrict
{
    use TeslaHelper;
    use VariableProfileHelper;

    public function Create(): void
    {
        //Never delete this line!
        parent::Create();

        $this->ConnectParent('{72887112-E270-FFC1-04AC-817D82F42027}');

        $this->RegisterPropertyString('VIN', '');
        $this->RegisterPropertyInteger('Interval', 60);

        $this->RegisterVariableString('gps_as_of', $this->Translate('GPS as of'));
        $this->RegisterVariableInteger('heading', $this->Translate('Heading'));
        $this->RegisterVariableString('latitude', $this->Translate('Latitude'));
        $this->RegisterVariableString('longitude', $this->Translate('Longitude'));
        $this->RegisterVariableString('native_latitude', $this->Translate('Native Latitude'));
        $this->RegisterVariableInteger('native_location_supported', $this->Translate('Native Location Supported'));
        $this->RegisterVariableString('native_longitude', $this->Translate('Native Longitude'));
        $this->RegisterVariableString('native_type', $this->Translate('Native Type'));
        $this->RegisterVariableInteger('power', $this->Translate('Power'));
        $this->RegisterVariableString('shift_state', $this->Translate('Shift State'));
        $this->RegisterVariableString('speed', $this->Translate('Speed'));
        $this->RegisterVariableString('timestamp', $this->Translate('Timestamp'));
        $this->RegisterVariableFloat('active_route_latitude', $this->Translate('Active Route Latitude'));
        $this->RegisterVariableFloat('active_route_longitude', $this->Translate('Active Route Longitude'));
        $this->RegisterVariableInteger('active_route_traffic_minutes_delay', $this->Translate('Active Route Traffic Minutes Delay'));
        $this->RegisterVariableInteger('active_route_energy_at_arrival', $this->Translate('Active Route Energy at arrival'));
        $this->RegisterVariableFloat('active_route_miles_to_arrival', $this->Translate('Active Route Miles to arrival'));
        $this->RegisterVariableFloat('active_route_route_destination', $this->Translate('Active Route destination'));

        $this->RegisterTimer('Tesla_UpdateDrive', 0, 'Tesla_FetchData($_IPS[\'TARGET\']);');
    }

    public function Destroy(): void
    {
        $this->UnregisterTimer('Tesla_UpdateDrive');
    }

    public function ApplyChanges(): void
    {

        //Never delete this line!
        parent::ApplyChanges();
    }

    public function ReceiveData($JSONString): string
    {
        $driveState = json_decode($JSONString)->response->drive_state ?? null;
        if (!is_object($driveState)) {
            return '';
        }

        foreach ($driveState as $key => $value) {
            if (in_array($key, ['latitude', 'longitude', 'gps_as_of'], true)) {
                continue;
            }
            if (@$this->GetIDForIdent($key) != false) {
                $this->SetValue($key, $value);
            } else {
                $this->SendDebug('Variable not exist', 'Key: ' . $key, 0);
            }
        }
        $this->updateGPSPosition($driveState);
        return '';
    }

    private function updateGPSPosition(stdClass $driveState): void
    {
        $latitude = $driveState->latitude ?? null;
        $longitude = $driveState->longitude ?? null;
        $gpsAsOf = $driveState->gps_as_of ?? null;
        $timestamp = is_int($gpsAsOf) || is_string($gpsAsOf) ? filter_var($gpsAsOf, FILTER_VALIDATE_INT) : false;
        if (!is_numeric($latitude) || abs((float) $latitude) > 90 || !is_numeric($longitude) || abs((float) $longitude) > 180
            || $timestamp === false || $timestamp <= max(0, (int) $this->GetValue('gps_as_of'))) {
            return;
        }

        // An empty time marks the write in progress. Publish the time once both coordinates are available.
        if ($this->SetValue('gps_as_of', '') && $this->SetValue('latitude', (string) $latitude)
            && $this->SetValue('longitude', (string) $longitude)) {
            $this->SetValue('gps_as_of', (string) $timestamp);
        }
    }
}
