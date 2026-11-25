# Vehicle location

Enable **Include vehicle location** in the Tesla Vehicle Connector to request
location in its normal vehicle update. Tesla requires the `vehicle_location`
permission and may display a location-sharing icon in the vehicle.

The Tesla Vehicle Drive instance publishes `latitude`, `longitude` and
`gps_as_of` from the same GPS fix. Its string variables keep their existing
types. `gps_as_of` contains Unix seconds. It is empty while a new fix is
being written and receives the measurement time after both coordinates.
Incomplete reports keep the last complete fix.

[Tesla Fleet API](https://developer.tesla.com/docs/fleet-api/endpoints/vehicle-endpoints#vehicle-data)
