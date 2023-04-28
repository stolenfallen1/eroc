import axios from "axios";

const baseURL = '/'
const baseURLAPI = '/api'

const httpClient = axios.create({
    baseURL
});

const httpApiClient = axios.create({
    baseURL: baseURLAPI
});


export { httpClient, httpApiClient };