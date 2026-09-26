import { publicAssetUrl } from './publicAssets'

export function resolveRoomImages(roomType) {
    const list = []
    if (Array.isArray(roomType?.images)) {
        for (const path of roomType.images) {
            const url = publicAssetUrl(path)
            if (url && !list.includes(url)) list.push(url)
        }
    }
    const single = publicAssetUrl(roomType?.image)
    if (single && !list.includes(single)) list.push(single)
    return list
}

export function roomImageUrl(roomType, index = 0) {
    return resolveRoomImages(roomType)[index] ?? null
}
