/**
 * Settings JavaScript
 * Скрипты для страницы настроек
 */

// Переменная для хранения текущей категории
let currentCategory = '';

// Инициализация при загрузке DOM
document.addEventListener('DOMContentLoaded', function() {
    // Получаем текущую категорию из URL
    const urlParams = new URLSearchParams(window.location.search);
    currentCategory = urlParams.get('category') || 'general';
    
    // Обработка выбора категории в форме добавления настройки
    initCategorySelect();
    
    // Обработчики для кнопок удаления настроек
    initDeleteButtons();
    
    // Валидация форм перед отправкой
    initFormValidation();
    
    // Инициализация подсказок
    initTooltips();
});

// Инициализация выбора категории
function initCategorySelect() {
    const categorySelect = document.getElementById('category_select');
    const newCategoryContainer = document.getElementById('new_category_container');
    const newCategoryInput = document.getElementById('new_category');
    
    if (categorySelect) {
        categorySelect.addEventListener('change', function() {
            if (this.value === 'new_category') {
                newCategoryContainer.style.display = 'block';
                newCategoryInput.setAttribute('required', 'required');
            } else {
                newCategoryContainer.style.display = 'none';
                newCategoryInput.removeAttribute('required');
            }
        });
    }
}

// Инициализация кнопок удаления
function initDeleteButtons() {
    document.querySelectorAll('.delete-setting').forEach(function(button) {
        button.addEventListener('click', function() {
            const key = this.getAttribute('data-key');
            
            document.getElementById('delete_key').value = key;
            document.getElementById('delete_setting_key').textContent = key;
        });
    });
}

// Инициализация валидации форм
function initFormValidation() {
    document.querySelectorAll('form[data-validate="true"]').forEach(function(form) {
        form.addEventListener('submit', function(event) {
            if (!validateForm(this)) {
                event.preventDefault();
            }
        });
    });
}

// Инициализация подсказок
function initTooltips() {
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
}

// Функция для переключения видимости пароля
function togglePasswordVisibility(inputId) {
    const input = document.getElementById(inputId);
    const button = input.nextElementSibling.querySelector('i');
    
    if (input.type === 'password') {
        input.type = 'text';
        button.classList.remove('bi-eye');
        button.classList.add('bi-eye-slash');
    } else {
        input.type = 'password';
        button.classList.remove('bi-eye-slash');
        button.classList.add('bi-eye');
    }
}

// Функция для проверки соединения с интеграцией
function testIntegration(integration) {
    // Показываем индикатор загрузки
    const resultContainer = document.getElementById('integration-test-result');
    resultContainer.style.display = 'block';
    resultContainer.innerHTML = '<div class="d-flex justify-content-center"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Проверка соединения...</span></div></div>';
    
    // Отправляем AJAX-запрос
    fetch('/settings/test-integration', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'integration=' + encodeURIComponent(integration)
    })
    .then(response => response.json())
    .then(data => {
        // Отображаем результат
        let resultHTML = '';
        if (data.success) {
            resultHTML = `
                <div class="alert alert-success">
                    <i class="bi bi-check-circle-fill me-2"></i>
                    ${data.message}
                </div>
            `;
            
            // Если есть дополнительные данные, показываем их
            if (data.data) {
                let detailsHTML = '<ul class="list-group mt-2">';
                for (const [key, value] of Object.entries(data.data)) {
                    if (typeof value === 'object') {
                        detailsHTML += `<li class="list-group-item"><strong>${key}:</strong> ${JSON.stringify(value)}</li>`;
                    } else {
                        detailsHTML += `<li class="list-group-item"><strong>${key}:</strong> ${value}</li>`;
                    }
                }
                detailsHTML += '</ul>';
                resultHTML += detailsHTML;
            }
        } else {
            resultHTML = `
                <div class="alert alert-danger">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    ${data.message}
                </div>
            `;
        }
        
        resultContainer.innerHTML = resultHTML;
    })
    .catch(error => {
        resultContainer.innerHTML = `
            <div class="alert alert-danger">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                Ошибка при выполнении запроса: ${error.message}
            </div>
        `;
    });
}

// Функция валидации формы
function validateForm(form) {
    let isValid = true;
    
    // Сбрасываем предыдущие ошибки
    form.querySelectorAll('.is-invalid').forEach(function(element) {
        element.classList.remove('is-invalid');
    });
    
    // Проверка обязательных полей
    form.querySelectorAll('[required]').forEach(function(input) {
        if (!input.value.trim()) {
            input.classList.add('is-invalid');
            isValid = false;
        }
    });
    
    // Проверка полей с шаблоном (pattern)
    form.querySelectorAll('[pattern]').forEach(function(input) {
        if (input.value.trim() && !new RegExp(input.getAttribute('pattern')).test(input.value)) {
            input.classList.add('is-invalid');
            isValid = false;
        }
    });
    
    return isValid;
}