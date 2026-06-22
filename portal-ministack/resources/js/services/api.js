import axios from "axios";

// Mengambil URL peladen dari fail .env yang telah Anda atur
const API_BASE_URL = import.meta.env.VITE_API_BASE_URL;

// Membuat instansiasi Axios khusus untuk portal MiniStack
const apiClient = axios.create({
    baseURL: API_BASE_URL,
    headers: {
        "Content-Type": "application/json",
        Accept: "application/json",
    },
});

// Interceptor Request: Menyisipkan token sebelum permintaan dikirim ke peladen
apiClient.interceptors.request.use(
    (config) => {
        // Mengambil token yang tersimpan (misalnya dari localStorage saat pengguna Login)
        const token = localStorage.getItem("auth_token");

        if (token) {
            config.headers.Authorization = `Bearer ${token}`;
        }
        return config;
    },
    (error) => {
        return Promise.reject(error);
    },
);

// --- Modul Layanan IaaS & Admin ---

export const IaasService = {
    // [User] Checkout Paket IaaS
    checkout: async (planId, paymentMethod) => {
        const response = await apiClient.post("/iaas/checkout", {
            plan_id: planId,
            metode_bayar: paymentMethod,
        });
        return response.data;
    },

    // [User] Ambil Riwayat Langganan
    getSubscriptions: async () => {
    const response = await apiClient.get("/iaas/subscriptions");
    return response.data;
    },

    getActivityLogs: async () => {
        const response = await apiClient.get("/iaas/logs");
        return response.data;
    },

    // [Admin] Verifikasi Pembayaran
    verifyPayment: async (paymentId) => {
        const response = await apiClient.patch(
            `/admin/payments/${paymentId}/verify`,
        );
        return response.data;
    },

    // [Admin] Ubah Status Kredensial
    toggleCredential: async (credentialId) => {
        const response = await apiClient.patch(
            `/admin/credentials/${credentialId}/toggle`,
        );
        return response.data;
    },
};

export default apiClient;
