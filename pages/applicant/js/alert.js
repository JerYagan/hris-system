window.showSuccessModal = function (message) {
    const modal = document.createElement('div');

    modal.innerHTML = `
        <div class="fixed inset-0 bg-black/40 flex items-center justify-center z-50">
            <div class="bg-white rounded-lg w-full max-w-md p-6 shadow-lg">
                <div class="flex items-center gap-3 mb-4">
                    <span class="material-symbols-outlined text-green-700 text-3xl">
                        check_circle
                    </span>
                    <h2 class="text-lg font-semibold text-gray-800">
                        Application Submitted
                    </h2>
                </div>

                <p class="text-sm text-gray-600 mb-6">
                    ${message}
                </p>

                <div class="flex justify-end">
                    <button id="closeSuccessModal"
                            class="px-4 py-2 bg-green-700 text-white rounded-md hover:bg-green-800">
                        Continue
                    </button>
                </div>
            </div>
        </div>
    `;

    document.body.appendChild(modal);

    document.getElementById('closeSuccessModal').onclick = () => {
        modal.remove();
        window.location.href = 'applications.php';
    };
};
