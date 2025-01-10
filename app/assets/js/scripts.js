/**
 * JavaScript functions for form validation and error handling
 * Funções JavaScript para validação de formulário e manipulação de erros
 */

/**
 * Validates the form before submission
 * 
 * Checks:
 * - If URL is not empty
 * - If URL starts with http:// or https://
 * - If URL has a valid format
 * 
 * @returns {boolean} True if URL is valid, False otherwise
 * 
 * Valida o formulário antes do envio
 * 
 * Verifica:
 * - Se a URL não está vazia
 * - Se a URL começa com http:// ou https://
 * - Se a URL tem um formato válido
 * 
 * @returns {boolean} True se a URL for válida, False caso contrário
 */
function validateForm() {
    const urlInput = document.getElementById('url');
    const submitButton = document.querySelector('button[type="submit"]');
    const url = urlInput.value.trim();
    
    // Check if URL is not empty
    // Verifica se a URL não está vazia
    if (!url) {
        showError('Por favor, insira uma URL');
        return false;
    }

    // Check if URL starts with http:// or https://
    // Verifica se a URL começa com http:// ou https://
    if (!/^https?:\/\//i.test(url)) {
        showError('A URL deve começar com http:// ou https://');
        return false;
    }

    // Try to create a URL object to validate format
    // Tenta criar um objeto URL para validar o formato
    try {
        new URL(url);
    } catch (e) {
        showError('Formato de URL inválido');
        return false;
    }

    // Disable input and button
    // Desabilita o input e o botão
    urlInput.readonly = true;
    submitButton.disabled = true;
    
    // Add Tailwind disabled classes
    // Adiciona classes de disabled do Tailwind
    submitButton.classList.add('cursor-wait', 'disabled:bg-blue-400');
    submitButton.classList.remove('hover:bg-blue-700');

    urlInput.classList.add('cursor-wait', 'disabled:bg-gray-50', 'focus:outline-none');

    // Add loading state to button
    // Adiciona estado de loading ao botão
    submitButton.innerHTML = `
        <img src="assets/svg/search.svg" class="w-6 h-6 mr-3" alt="Search">
        Analisando...
    `;

    return true;
}

/**
 * Displays an error message below the form
 * 
 * Removes any existing error message before displaying the new one.
 * The message is displayed with an error icon and red formatting.
 * 
 * @param {string} message Error message to be displayed
 * 
 * Exibe uma mensagem de erro abaixo do formulário
 * 
 * Remove qualquer mensagem de erro existente antes de exibir a nova.
 * A mensagem é exibida com um ícone de erro e formatação em vermelho.
 * 
 * @param {string} message Mensagem de erro a ser exibida
 */
function showError(message) {
    const form = document.getElementById('urlForm');
    const existingError = form.querySelector('.error-message');
    
    // Remove previous error message if it exists
    // Remove mensagem de erro anterior se existir
    if (existingError) {
        existingError.remove();
    }

    // Create and add new error message
    // Cria e adiciona nova mensagem de erro
    const errorDiv = document.createElement('div');
    errorDiv.className = 'error-message mt-4 text-base text-red-600';
    errorDiv.innerHTML = `
        <img src="assets/svgs/error.svg" class="inline-block w-5 h-5 mr-2" alt="Error icon">
        ${message}`;
    
    form.appendChild(errorDiv);
}

/**
 * Service Worker registration for PWA functionality
 * Registers a service worker to enable offline capabilities and PWA features
 * 
 * Registro do Service Worker para funcionalidade PWA
 * Registra um service worker para habilitar recursos offline e funcionalidades PWA
 */
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/service-worker.js')
            .then(() => {
                // Service Worker registered successfully
                // Service Worker registrado com sucesso
            })
            .catch(() => {
                // Service Worker registration failed
                // Falha no registro do Service Worker
            });
    });
}
