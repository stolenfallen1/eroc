import {httpApiClient} from "@global/axios";
import { store } from "../store"
const items = "suppliers";

httpApiClient.defaults.headers.common['Authorization'] = 'Bearer ' + store.getters.user.api_token
export const apiGetAllSuppliers = (query)  => httpApiClient.get(`${items}?`+query);