/**
 * Основные функции JavaScript для приложения
 */

// Инициализация при загрузке DOM
document.addEventListener('DOMContentLoaded', function() {
    // Инициализация всплывающих подсказок
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Обработка flash-сообщений
    setTimeout(function() {
        var flashMessages = document.querySelectorAll('.alert-dismissible');
        flashMessages.forEach(function(message) {
            var alert = new bootstrap.Alert(message);
            alert.close();
        });
    }, 5000);
    
    // Инициализация выпадающих списков с поиском
    if (typeof $.fn.select2 !== 'undefined') {
        $('.select2').select2({
            language: 'ru',
            placeholder: 'Выберите значение',
            allowClear: true
        });
    }
});

/**
 * Подтверждение действия
 * 
 * @param {string} message Сообщение для подтверждения
 * @param {function} callback Функция, которая будет вызвана при подтверждении
 */
function confirmAction(message, callback) {
    if (confirm(message)) {
        callback();
    }
}

/**
 * Отправка AJAX запроса
 * 
 * @param {string} url URL запроса
 * @param {string} method Метод запроса (GET, POST)
 * @param {object} data Данные для отправки
 * @param {function} successCallback Функция, вызываемая при успешном запросе
 * @param {function} errorCallback Функция, вызываемая при ошибке
 */
function sendAjaxRequest(url, method, data, successCallback, errorCallback) {
    $.ajax({
        url: url,
        type: method,
        data: data,
        dataType: 'json',
        success: function(response) {
            if (typeof successCallback === 'function') {
                successCallback(response);
            }
        },
        error: function(xhr, status, error) {
            if (typeof errorCallback === 'function') {
                errorCallback(xhr, status, error);
            } else {
                console.error('AJAX Error:', error);
                alert('Произошла ошибка при выполнении запроса.');
            }
        }
    });
}

/**
 * Валидация формы
 * 
 * @param {string} formId ID формы для валидации
 * @returns {boolean} Результат валидации
 */
function validateForm(formId) {
    const form = document.getElementById(formId);
    if (!form) return false;
    
    let isValid = true;
    const inputs = form.querySelectorAll('input, select, textarea');
    
    // Очистка предыдущих ошибок
    inputs.forEach(input => {
        input.classList.remove('is-invalid');
        input.classList.remove('is-valid');
        const feedbackElement = form.querySelector(`#${input.id}-feedback`);
        if (feedbackElement) {
            feedbackElement.style.display = 'none';
        }
    });
    
    // Проверка обязательных полей
    inputs.forEach(input => {
        if (input.hasAttribute('required') && !input.value.trim()) {
            isValid = false;
            input.classList.add('is-invalid');
            const feedbackElement = form.querySelector(`#${input.id}-feedback`);
            if (feedbackElement) {
                feedbackElement.textContent = 'Это поле обязательно для заполнения';
                feedbackElement.style.display = 'block';
            }
        } else if (input.value.trim()) {
            input.classList.add('is-valid');
        }
    });
    
    return isValid;
}