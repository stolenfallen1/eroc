import Axios from "@global/axios";
const items = "api/units";

export const apiGetAllUnits = (query)  => Axios.get(`${items}?`+query);