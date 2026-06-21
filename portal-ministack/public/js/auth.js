/**
 * auth.js
 * Dipakai bersama oleh halaman login & register (ChromaStack).
 * - togglePassword()      : tampil/sembunyikan isi field password
 * - initPasswordMatch()   : validasi konfirmasi password secara real-time
 * - initSubmitLoading()   : kasih loading state untuk form tradisional (Register)
 * - initApiLogin()        : menangani proses login via REST API & simpan Token
 */

function togglePassword(id, btn) {
    const input = document.getElementById(id);
    const icon = btn.querySelector("i");
    if (!input) return;

    if (input.type === "password") {
        input.type = "text";
        icon.className = "fa fa-eye-slash";
    } else {
        input.type = "password";
        icon.className = "fa fa-eye";
    }
}

function initPasswordMatch() {
    const password = document.getElementById("password");
    const confirmation = document.getElementById("password_confirmation");

    if (!password || !confirmation) return;

    let hint =
        confirmation.parentElement.parentElement.querySelector(
            ".form-match-hint",
        );
    if (!hint) {
        hint = document.createElement("span");
        hint.className = "form-match-hint";
        confirmation.parentElement.parentElement.appendChild(hint);
    }

    function checkMatch() {
        if (confirmation.value.length === 0) {
            hint.textContent = "";
            hint.className = "form-match-hint";
            return true;
        }

        const isMatch = password.value === confirmation.value;
        hint.textContent = isMatch ? "Password cocok ✓" : "Password belum sama";
        hint.className =
            "form-match-hint " + (isMatch ? "is-match" : "is-mismatch");
        return isMatch;
    }

    password.addEventListener("input", checkMatch);
    confirmation.addEventListener("input", checkMatch);

    const form = confirmation.closest("form");
    if (form) {
        form.addEventListener("submit", function (e) {
            if (!checkMatch() && confirmation.value.length > 0) {
                e.preventDefault();
                confirmation.focus();
            }
        });
    }
}

function initSubmitLoading() {
    // Fungsi ini kita batasi agar tidak mengganggu form API Login
    document
        .querySelectorAll(".auth-form:not(#apiLoginForm)")
        .forEach(function (form) {
            form.addEventListener("submit", function () {
                if (form.dataset.blocked === "true") return;

                const btn = form.querySelector('button[type="submit"]');
                if (!btn || btn.disabled) return;

                btn.dataset.originalHtml = btn.innerHTML;
                btn.disabled = true;
                btn.classList.add("is-loading");
                btn.innerHTML =
                    '<i class="fa fa-spinner fa-spin"></i> Memproses...';
            });
        });
}

/**
 * Fungsi baru untuk menangani otentikasi via API
 */
function initApiLogin() {
    const loginForm = document.getElementById("apiLoginForm");

    // Jika elemen tidak ditemukan (misal: user sedang di halaman Register), hentikan fungsi
    if (!loginForm) return;

    loginForm.addEventListener("submit", async function (e) {
        e.preventDefault(); // Mencegah reload halaman

        const email = document.getElementById("email").value;
        const password = document.getElementById("password").value;
        const submitBtn = loginForm.querySelector('button[type="submit"]');

        // Modifikasi antarmuka tombol (Indikator Pemuatan)
        const originalBtnText = submitBtn.innerHTML;
        submitBtn.disabled = true;
        submitBtn.classList.add("is-loading");
        submitBtn.innerHTML =
            '<i class="fa fa-spinner fa-spin"></i> Memvalidasi...';

        try {
            // Eksekusi pemanggilan REST API menggunakan Axios
            // KITA TAMBAHKAN HEADERS AGAR LARAVEL TAHU KITA MINTA JSON
            const response = await axios.post(
                "/login",
                {
                    email: email,
                    password: password,
                },
                {
                    headers: {
                        Accept: "application/json",
                        "X-Requested-With": "XMLHttpRequest",
                    },
                },
            );

            // Amankan Bearer Token ke dalam Local Storage Peramban
            const token = response.data.token;

            // Validasi tambahan: Pastikan token benar-benar ada sebelum disimpan
            if (token) {
                localStorage.setItem("auth_token", token);
                window.location.href = "/dashboard";
            } else {
                alert(
                    "Login berhasil, tetapi sistem gagal menerbitkan Token API.",
                );
            }
        } catch (error) {
            // Tangani Galat (Tampilkan pesan error dari Backend)
            const errorMsg =
                error.response?.data?.message ||
                "Email atau kata sandi tidak valid. Silakan coba lagi.";

            // Tampilkan pesan menggunakan alert atau injeksi ke dalam elemen DOM
            alert(errorMsg);

            // Kembalikan status tombol
            submitBtn.disabled = false;
            submitBtn.classList.remove("is-loading");
            submitBtn.innerHTML = originalBtnText;
        }
    });
}

// Inisialisasi seluruh fungsi saat dokumen selesai dimuat
document.addEventListener("DOMContentLoaded", function () {
    initPasswordMatch();
    initSubmitLoading();
    initApiLogin(); // Panggil fungsi API Login
});
