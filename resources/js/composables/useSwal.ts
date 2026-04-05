import Swal, { type SweetAlertOptions } from 'sweetalert2'

export function useSwal() {
    return {
        fire: (options: SweetAlertOptions) => {
            return Swal.fire({
                ...options,
                scrollbarPadding: false,
                heightAuto: false,
            })
        }
    }
}