import {httpApiClient} from "@global/axios";
const items = "units";

export const apiGetAllUnits = (query)  => httpApiClient.get(`${items}?`+query);