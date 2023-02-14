import Axios from "@global/axios";
const items = "api/items";

export const apiGetAllBuildItems = (query)  => Axios.get(`${items}?`+query);