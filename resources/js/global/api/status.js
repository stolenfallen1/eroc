import {httpApiClient} from "@global/axios";
import { store } from "../store"
const status = "status";
httpApiClient.defaults.headers.common['Authorization'] = 'Bearer ' + store.getters.user.api_token
export const apiGetAllStatus = (query)  => httpApiClient.get(`${status}?`+query);