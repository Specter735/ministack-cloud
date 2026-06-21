import React, { useState } from "react";
// Pastikan jalur (path) import ini sesuai dengan lokasi fail api.js Anda
import { IaasService } from "../services/api";

const CheckoutPage = () => {
    // State untuk menyimpan pilihan pengguna
    const [planId, setPlanId] = useState(1);
    const [metodeBayar, setMetodeBayar] = useState("Transfer Bank");

    // State untuk menampilkan indikator pemuatan dan pesan hasil
    const [loading, setLoading] = useState(false);
    const [pesan, setPesan] = useState(null);

    // Fungsi yang dieksekusi saat tombol Checkout ditekan
    const handleSumbitCheckout = async (e) => {
        e.preventDefault(); // Mencegah halaman termuat ulang (refresh)
        setLoading(true);
        setPesan(null);

        try {
            // Memanggil fungsi dari api.js dan mengirimkan data state
            const hasil = await IaasService.checkout(planId, metodeBayar);

            // Menampilkan pesan sukses dari Backend ("Pesanan berhasil dibuat...")
            setPesan({ tipe: "sukses", teks: hasil.message });
        } catch (error) {
            // Menangkap dan menampilkan pesan galat dari Backend
            const errorMsg =
                error.response?.data?.message || "Terjadi kesalahan jaringan.";
            setPesan({ tipe: "galat", teks: errorMsg });
        } finally {
            setLoading(false);
        }
    };

    return (
        <div style={{ padding: "20px", maxWidth: "500px", margin: "0 auto" }}>
            <h2>Checkout Paket IaaS</h2>

            <form onSubmit={handleSumbitCheckout}>
                <div style={{ marginBottom: "15px" }}>
                    <label>Pilih Paket IaaS:</label>
                    <select
                        value={planId}
                        onChange={(e) => setPlanId(Number(e.target.value))}
                        style={{
                            display: "block",
                            width: "100%",
                            padding: "8px",
                        }}
                    >
                        <option value={1}>Paket Mahasiswa (ID: 1)</option>
                        <option value={2}>Paket Profesional (ID: 2)</option>
                    </select>
                </div>

                <div style={{ marginBottom: "15px" }}>
                    <label>Metode Pembayaran:</label>
                    <select
                        value={metodeBayar}
                        onChange={(e) => setMetodeBayar(e.target.value)}
                        style={{
                            display: "block",
                            width: "100%",
                            padding: "8px",
                        }}
                    >
                        <option value="Transfer Bank">Transfer Bank</option>
                        <option value="E-Wallet">E-Wallet</option>
                    </select>
                </div>

                <button
                    type="submit"
                    disabled={loading}
                    style={{
                        padding: "10px 15px",
                        cursor: loading ? "not-allowed" : "pointer",
                    }}
                >
                    {loading ? "Memproses..." : "Checkout Sekarang"}
                </button>
            </form>

            {/* Kotak untuk menampilkan notifikasi berhasil atau gagal */}
            {pesan && (
                <div
                    style={{
                        marginTop: "20px",
                        padding: "10px",
                        backgroundColor:
                            pesan.tipe === "sukses" ? "#d4edda" : "#f8d7da",
                        color: pesan.tipe === "sukses" ? "#155724" : "#721c24",
                    }}
                >
                    {pesan.teks}
                </div>
            )}
        </div>
    );
};

export default CheckoutPage;
