import {httpApiClient} from "@global/axios";
import { store } from "../store"
const items = "items";
httpApiClient.defaults.headers.common['Authorization'] = 'Bearer ' + store.getters.user.api_token

export const apiGetAllBuildItems = (query)  => httpApiClient.get(`${items}?`+query);