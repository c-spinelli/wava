document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('workout-form');
    if (!form) return;
  
    const list = document.getElementById('workout-list');
  
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
  
      const workout = await response.json();
  
      // Recargar para reflejar progreso y botones
        window.location.reload();

    });
  });
  