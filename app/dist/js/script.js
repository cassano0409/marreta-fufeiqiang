function validateForm(){var r=document.getElementById("url"),e=document.querySelector('button[type="submit"]'),t=r.value.trim();if(!t)return showError("Por favor, insira uma URL"),!1;if(!/^https?:\/\//i.test(t))return showError("A URL deve começar com http:// ou https://"),!1;try{new URL(t)}catch(r){return showError("Formato de URL inválido"),!1}return r.readonly=!0,e.disabled=!0,e.classList.add("cursor-wait","disabled:bg-blue-400"),e.classList.remove("hover:bg-blue-700"),r.classList.add("cursor-wait","disabled:bg-gray-50","focus:outline-none"),e.innerHTML=`
        <img src="assets/svg/search.svg" class="w-6 h-6 mr-3" alt="Search">
        Analisando...
    `,!0}function showError(r){var e=document.getElementById("urlForm"),t=e.querySelector(".error-message"),t=(t&&t.remove(),document.createElement("div"));t.className="error-message mt-4 text-base text-red-600",t.innerHTML=`
        <img src="assets/svgs/error.svg" class="inline-block w-5 h-5 mr-2" alt="Error icon">
        `+r,e.appendChild(t)}"serviceWorker"in navigator&&window.addEventListener("load",()=>{navigator.serviceWorker.register("/service-worker.js").then(()=>{}).catch(()=>{})});
//# sourceMappingURL=script.js.map
