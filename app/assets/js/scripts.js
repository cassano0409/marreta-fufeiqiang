/**
 * Funções JavaScript para validação de formulário e manipulação de erros
 */

/**
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
    
    // Verifica se a URL não está vazia
    if (!url) {
        showError('Por favor, insira uma URL');
        return false;
    }

    // Verifica se a URL começa com http:// ou https://
    if (!/^https?:\/\//i.test(url)) {
        showError('A URL deve começar com http:// ou https://');
        return false;
    }

    // Tenta criar um objeto URL para validar o formato
    try {
        new URL(url);
    } catch (e) {
        showError('Formato de URL inválido');
        return false;
    }

    // Desabilita o input e o botão
    urlInput.disabled = true;
    submitButton.disabled = true;
    
    // Adiciona classes de disabled do Tailwind
    submitButton.classList.add('cursor-not-allowed', 'disabled:bg-blue-400');
    submitButton.classList.remove('hover:bg-blue-700');

    // Adiciona estado de loading ao botão
    submitButton.innerHTML = `
        <img src="/assets/svg/refresh.svg" class="animate-spin w-5 h-5 mr-2">
        Analisando...
    `;

    return true;
}

/**
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
    
    // Remove mensagem de erro anterior se existir
    if (existingError) {
        existingError.remove();
    }

    // Cria e adiciona nova mensagem de erro
    const errorDiv = document.createElement('div');
    errorDiv.className = 'error-message mt-4 text-base text-red-600';
    errorDiv.innerHTML = `
        <img src="assets/svgs/error.svg" class="inline-block w-5 h-5 mr-2" alt="Error icon">
        ${message}`;
    
    form.appendChild(errorDiv);
}
