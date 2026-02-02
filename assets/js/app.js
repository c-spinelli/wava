document.addEventListener('DOMContentLoaded', () => {
  const form = document.getElementById('workout-form');
  if (!form) return;

  const list = document.getElementById('workout-list');
  const toggleBtn = document.getElementById('toggleWorkoutForm');

  const iconMap = {
    running: "🏃",
    strength: "🏋️",
    boxing: "🥊",
    yoga: "🧘",
    cycling: "🚴",
    walking: "🚶"
  };

  const labelMap = {
    running: "Running",
    strength: "Strength",
    boxing: "Boxing",
    yoga: "Yoga",
    cycling: "Cycling",
    walking: "Walking"
  };

  form.addEventListener('submit', async (e) => {
    e.preventDefault();

    const formData = new FormData(form);

    const response = await fetch('../api/workouts_create.php', {
      method: 'POST',
      body: formData
    });

    if (!response.ok) {
      alert('Error al añadir ejercicio');
      return;
    }

    const data = await response.json();
    if (data.error) {
      alert(data.error);
      return;
    }

    const type = data.workout_type;
    const minutes = data.minutes;
    const notes = data.notes || '';

    const icon = iconMap[type] || "🎯";
    const title = labelMap[type] || (type ? type[0].toUpperCase() + type.slice(1) : "Workout");

    const card = document.createElement('div');
    card.className = `workout-card type-${type}`;
    card.dataset.workoutId = data.id;

    card.innerHTML = `
      <div class="workout-icon">${icon}</div>
      <div class="workout-meta">
        <div class="workout-title">${escapeHtml(title)}</div>
        <div class="workout-sub">${minutes} minutos${notes ? ' · ' + escapeHtml(notes) : ''}</div>
      </div>
      <button class="trash" type="button" data-workout-id="${data.id}" aria-label="Eliminar">🗑️</button>
    `;

    list.prepend(card);

    // ✅ ACTUALIZA KPI EJERCICIO (sin reload)
    if (typeof data.total_exercise_minutes !== "undefined") {
      updateExerciseKpi(data.total_exercise_minutes);
      updateHistoryCalendarExercise(data.total_exercise_minutes);
    }

    form.reset();
    form.classList.add('is-collapsed');
    toggleBtn?.blur();
  });
});

function escapeHtml(str) {
  return String(str)
    .replaceAll('&', '&amp;')
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;')
    .replaceAll('"', '&quot;')
    .replaceAll("'", '&#039;');
}

  
  document.addEventListener("DOMContentLoaded", () => {
    const btn = document.getElementById("toggleWorkoutForm");
    const form = document.getElementById("workout-form");
    if (!btn || !form) return;
  
    btn.addEventListener("click", () => {
      form.classList.toggle("is-collapsed");
    });
  });
  
  /*const iconMap = {
    running: "🏃",
    strength: "🏋️",
    boxing: "🥊",
    yoga: "🧘",
    cycling: "🚴",
    walking: "🚶"
  };
  
  const labelMap = {
    running: "Running",
    strength: "Strength",
    boxing: "Boxing",
    yoga: "Yoga",
    cycling: "Cycling",
    walking: "Walking"
  };
  
  const icon = iconMap[type] || "🎯";
  const title = labelMap[type] || (type ? type[0].toUpperCase() + type.slice(1) : "Workout");
  */

  document.addEventListener("click", async (e) => {
    const btn = e.target.closest(".trash");
    if (!btn) return;
  
    const id = btn.getAttribute("data-workout-id");
    if (!id) return;
  
    if (!confirm("¿Eliminar este ejercicio?")) return;
  
    const res = await fetch("../api/workouts_delete.php", {
      method: "POST",
      headers: {"Content-Type":"application/x-www-form-urlencoded"},
      body: new URLSearchParams({ workout_id: id })
    });
  
    if (!res.ok) {
      alert("No se pudo eliminar");
      return;
    }
  
    const data = await res.json();
  
    // sacar card del DOM
    const card = btn.closest(".workout-card");
    if (card) card.remove();
  
    // actualizar KPI
    if (typeof data.total_exercise_minutes !== "undefined"){
      updateExerciseKpi(data.total_exercise_minutes);
      updateHistoryCalendarExercise(data.total_exercise_minutes);

    }
  });
  
  function clamp(n, min, max){
    return Math.max(min, Math.min(max, n));
  }
  
  function updateExerciseKpi(totalMinutes){
    const ring = document.getElementById("kpi-exercise-ring");
    const valueEl = document.getElementById("kpi-exercise-value");
    const pctEl = document.getElementById("kpi-exercise-percent");
  
    if (valueEl) valueEl.textContent = totalMinutes;
  
    if (!ring) return;
  
    const goal = parseInt(ring.dataset.goal || "0", 10);
    const rawPct = goal > 0 ? Math.round((totalMinutes / goal) * 100) : 0;
  
    if (pctEl) pctEl.textContent = rawPct + "%";
    ring.style.setProperty("--p", clamp(rawPct, 0, 100));
  }

  const notice = document.querySelector('.notice.success');
if (notice) {
  setTimeout(() => {
    notice.style.opacity = '0';
    setTimeout(() => notice.remove(), 1000);
  }, 1000);
}

function ensureExerciseRow(dayEl) {
  let metrics = dayEl.querySelector(".day-metrics");
  if (!metrics) {
    // Si el día estaba vacío ("—"), creamos el contenedor y sacamos el placeholder
    const empty = dayEl.querySelector(".day-empty");
    if (empty) empty.remove();

    metrics = document.createElement("div");
    metrics.className = "day-metrics";
    dayEl.appendChild(metrics);
  }
  return metrics;
}

function upsertExerciseMetric(dayEl, minutes) {
  const metrics = ensureExerciseRow(dayEl);

  // Buscar si ya existe la fila de ejercicio (🏋️)
  let exRow = Array.from(metrics.querySelectorAll(".m")).find((row) => {
    const icon = row.querySelector(".i");
    return icon && icon.textContent.trim() === "🏋️";
  });

  if (minutes > 0) {
    if (!exRow) {
      exRow = document.createElement("div");
      exRow.className = "m";
      exRow.innerHTML = `<span class="i">🏋️</span><span></span>`;
      metrics.appendChild(exRow);
    }
    exRow.querySelector("span:last-child").textContent = `${minutes}m`;
  } else {
    // Si quedó en 0, borramos la fila si existía
    if (exRow) exRow.remove();

    // Si no quedan métricas, volver a mostrar "—"
    if (metrics.querySelectorAll(".m").length === 0) {
      metrics.remove();
      const empty = document.createElement("div");
      empty.className = "day-empty";
      empty.textContent = "—";
      dayEl.appendChild(empty);
    }
  }
}

function updateHistoryCalendarExercise(totalMinutes) {
  const selectedDateEl = document.getElementById("selected-date");
  if (!selectedDateEl) return;

  const dateStr = selectedDateEl.value;
  const dayEl = document.querySelector(`.calendar-cells .day[data-date="${CSS.escape(dateStr)}"]`);
  if (!dayEl) return;

  upsertExerciseMetric(dayEl, totalMinutes);
}

document.addEventListener("DOMContentLoaded", () => {
  const editor = document.getElementById("history-editor");
  if (editor) {
    editor.scrollIntoView({
      behavior: "smooth",
      block: "start"
    });
  }
});

document.addEventListener('DOMContentLoaded', () => {
  const ranges = document.querySelectorAll('input[type="range"][data-range-value-id]');
  if (!ranges.length) return;

  function formatValue(input) {
    const decimals = parseInt(input.dataset.rangeDecimals || "0", 10);
    const num = parseFloat(input.value);
    if (Number.isNaN(num)) return input.value;
    return decimals > 0 ? num.toFixed(decimals) : String(Math.round(num));
  }

  ranges.forEach((r) => {
    const id = r.dataset.rangeValueId;
    const out = document.querySelector(`[data-range-value-for="${id}"]`);
    if (!out) return;

    const update = () => { out.textContent = formatValue(r); };
    r.addEventListener('input', update);
    update();
  });
});


document.addEventListener('DOMContentLoaded', () => {
  const editBtn = document.getElementById('editToggleBtn');
  const saveBtn = document.getElementById('saveBtn');
  const form = document.querySelector('form.profile-form');
  if (!editBtn || !saveBtn || !form) return;

  const editables = Array.from(form.querySelectorAll('.js-editable'));
  if (!editables.length) return;

  // Guardamos snapshot inicial
  const initial = new Map();
  editables.forEach(el => {
    initial.set(el.name || el.dataset.rangeValueId || el.id || el, el.value);
  });

  let editMode = false;

  function setEditMode(on) {
    editMode = on;
    editables.forEach(el => { el.disabled = !on; });

    editBtn.innerHTML = on ? 'Cancelar' : '✏️ Editar';
    editBtn.classList.toggle('is-danger', on);


    // Al entrar/salir, recalculamos si hay cambios
    updateDirtyState();
  }

  function isDirty() {
    for (const el of editables) {
      const key = el.name || el.dataset.rangeValueId || el.id || el;
      if ((initial.get(key) ?? '') !== el.value) return true;
    }
    return false;
  }

  function updateDirtyState() {
    const dirty = editMode && isDirty();
    saveBtn.disabled = !dirty;
  }

  // Actualiza valores visibles al mover sliders
  function updateRangeOutputs() {
    const ranges = form.querySelectorAll('input[type="range"][data-range-value-id]');
    ranges.forEach(r => {
      const id = r.dataset.rangeValueId;
      const out = document.querySelector(`[data-range-value-for="${id}"]`);
      if (!out) return;

      const decimals = parseInt(r.dataset.rangeDecimals || "0", 10);
      const num = parseFloat(r.value);
      if (Number.isNaN(num)) return;

      out.textContent = decimals > 0 ? num.toFixed(decimals) : String(Math.round(num));
    });
  }

  // Escuchamos cambios
  editables.forEach(el => {
    el.addEventListener('input', () => {
      updateDirtyState();
      if (el.type === 'range') updateRangeOutputs();
    });
    el.addEventListener('change', () => {
      updateDirtyState();
      if (el.type === 'range') updateRangeOutputs();
    });
  });

  editBtn.addEventListener('click', () => {
    if (!editMode) {
      setEditMode(true);
      return;
    }

    // Cancelar: volver a valores iniciales
    editables.forEach(el => {
      const key = el.name || el.dataset.rangeValueId || el.id || el;
      el.value = initial.get(key) ?? el.value;
    });

    updateRangeOutputs();
    setEditMode(false);
  });

  saveBtn.addEventListener('click', () => {
    if (saveBtn.disabled) return;
    form.requestSubmit(); // envía el form aunque el botón esté fuera
  });
  

  // Estado inicial: modo lectura
  setEditMode(false);
  updateRangeOutputs();
});
