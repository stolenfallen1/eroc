import axios from "axios";
import { store } from "./store"
const baseURL = '/'
const baseURLAPI = '/api'

// axios.defaults.headers.common['Authorization'] = store.getState().session.token;
// axios.defaults.baseURL = '/';
const httpClient = axios.create({
  baseURL
});

const httpApiClient = axios.create({
  baseURL: baseURLAPI
});


// if(store.getters.user){
  // httpApiClient.request.use(function(config) {
  //   let getAuthToken = () => store.getters.user.api_token;
  //   config.headers.Authorization = `Bearer ${getAuthToken()}`;
  //   return config;
  // });
// }

export { httpClient, httpApiClient };