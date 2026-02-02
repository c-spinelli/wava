function isValidEmail(email) {
    // Suficiente para validación de frontend (no intentes RFC completo)
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
  }
  
  function setFieldError(input, message) {
    const container = input.closest(".field") || input.parentElement;
    let err = container.querySelector(".form-error");
  
    if (!err) {
      err = document.createElement("p");
      err.className = "form-error";
      container.appendChild(err);
    }
  
    err.textContent = message;
    input.setAttribute("aria-invalid", "true");
  }
  
  function clearFieldError(input) {
    const container = input.closest(".field") || input.parentElement;
    const err = container.querySelector(".form-error");
    if (err) err.textContent = "";
    input.removeAttribute("aria-invalid");
  }
  
  function attachValidation() {
    // LOGIN
    const loginForm = document.getElementById("loginForm");
    if (loginForm) {
      loginForm.addEventListener("submit", (e) => {
        const email = loginForm.querySelector('input[name="email"]');
        const password = loginForm.querySelector('input[name="password"]');
  
        let ok = true;
  
        clearFieldError(email);
        clearFieldError(password);
  
        if (!email.value.trim()) {
          setFieldError(email, "El email es obligatorio.");
          ok = false;
        } else if (!isValidEmail(email.value.trim())) {
          setFieldError(email, "El email no tiene un formato válido.");
          ok = false;
        }
  
        if (!password.value) {
          setFieldError(password, "La contraseña es obligatoria.");
          ok = false;
        }
  
        if (!ok) e.preventDefault();
      });
    }
  
    // REGISTER
    const registerForm = document.getElementById("registerForm");
    if (registerForm) {
      registerForm.addEventListener("submit", (e) => {
        const name = registerForm.querySelector('input[name="name"]');
        const email = registerForm.querySelector('input[name="email"]');
        const password = registerForm.querySelector('input[name="password"]');
        const confirm = registerForm.querySelector('input[name="confirm_password"]');
  
        let ok = true;
  
        if (name) clearFieldError(name);
        clearFieldError(email);
        clearFieldError(password);
        if (confirm) clearFieldError(confirm);
  
        if (name && !name.value.trim()) {
          setFieldError(name, "El nombre es obligatorio.");
          ok = false;
        }
  
        if (!email.value.trim()) {
          setFieldError(email, "El email es obligatorio.");
          ok = false;
        } else if (!isValidEmail(email.value.trim())) {
          setFieldError(email, "El email no tiene un formato válido.");
          ok = false;
        }
  
        if (!password.value) {
          setFieldError(password, "La contraseña es obligatoria.");
          ok = false;
        } else if (password.value.length < 8) {
          setFieldError(password, "La contraseña debe tener al menos 6 caracteres.");
          ok = false;
        }
  
        if (confirm) {
          if (!confirm.value) {
            setFieldError(confirm, "Repetí la contraseña.");
            ok = false;
          } else if (confirm.value !== password.value) {
            setFieldError(confirm, "Las contraseñas no coinciden.");
            ok = false;
          }
        }
  
        if (!ok) e.preventDefault();
      });
    }
  }
  
  document.addEventListener("DOMContentLoaded", attachValidation);
  