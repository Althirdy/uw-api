type CarrierInfo = {
    carrier: string;
    network: 'Smart' | 'Globe' | 'DITO' | 'Unknown';
};

// Prefix mappings for Philippine mobile carriers
const carrierPrefixes: Record<string, CarrierInfo> = {
    // Smart Communications
    '0813': { carrier: 'Smart', network: 'Smart' },
    '0908': { carrier: 'Smart', network: 'Smart' },
    '0918': { carrier: 'Smart', network: 'Smart' },
    '0919': { carrier: 'Smart', network: 'Smart' },
    '0920': { carrier: 'Smart', network: 'Smart' },
    '0921': { carrier: 'Smart', network: 'Smart' },
    '0928': { carrier: 'Smart', network: 'Smart' },
    '0929': { carrier: 'Smart', network: 'Smart' },
    '0930': { carrier: 'Smart', network: 'Smart' },
    '0938': { carrier: 'Smart', network: 'Smart' },
    '0939': { carrier: 'Smart', network: 'Smart' },
    '0940': { carrier: 'Smart', network: 'Smart' },
    '0946': { carrier: 'Smart', network: 'Smart' },
    '0947': { carrier: 'Smart', network: 'Smart' },
    '0948': { carrier: 'Smart', network: 'Smart' },
    '0949': { carrier: 'Smart', network: 'Smart' },
    '0950': { carrier: 'Smart', network: 'Smart' },
    '0951': { carrier: 'Smart', network: 'Smart' },
    '0961': { carrier: 'Smart', network: 'Smart' },
    '0970': { carrier: 'Smart', network: 'Smart' },
    '0981': { carrier: 'Smart', network: 'Smart' },
    '0982': { carrier: 'Smart', network: 'Smart' },
    '0983': { carrier: 'Smart', network: 'Smart' },
    '0984': { carrier: 'Smart', network: 'Smart' },
    '0985': { carrier: 'Smart', network: 'Smart' },
    '0998': { carrier: 'Smart', network: 'Smart' },
    '0999': { carrier: 'Smart', network: 'Smart' },

    // TNT (Talk 'N Text - Smart subsidiary)
    '0907': { carrier: 'TNT', network: 'Smart' },
    '0909': { carrier: 'TNT', network: 'Smart' },
    '0910': { carrier: 'TNT', network: 'Smart' },
    '0912': { carrier: 'TNT', network: 'Smart' },
    '0913': { carrier: 'TNT', network: 'Smart' },
    '0914': { carrier: 'TNT', network: 'Smart' },
    '0922': { carrier: 'TNT', network: 'Smart' },
    '0923': { carrier: 'TNT', network: 'Smart' },
    '0925': { carrier: 'TNT', network: 'Smart' },

    // Sun Cellular (now owned by Smart/PLDT)
    '0924': { carrier: 'Sun Cellular', network: 'Smart' },
    '0931': { carrier: 'Sun Cellular', network: 'Smart' },
    '0932': { carrier: 'Sun Cellular', network: 'Smart' },
    '0933': { carrier: 'Sun Cellular', network: 'Smart' },
    '0934': { carrier: 'Sun Cellular', network: 'Smart' },
    '0941': { carrier: 'Sun Cellular', network: 'Smart' },
    '0942': { carrier: 'Sun Cellular', network: 'Smart' },
    '0943': { carrier: 'Sun Cellular', network: 'Smart' },
    '0944': { carrier: 'Sun Cellular', network: 'Smart' },
    '0973': { carrier: 'Sun Cellular', network: 'Smart' },
    '0974': { carrier: 'Sun Cellular', network: 'Smart' },

    // Globe Telecom
    '0817': { carrier: 'Globe', network: 'Globe' },
    '0905': { carrier: 'Globe', network: 'Globe' },
    '0906': { carrier: 'Globe', network: 'Globe' },
    '0915': { carrier: 'Globe', network: 'Globe' },
    '0916': { carrier: 'Globe', network: 'Globe' },
    '0917': { carrier: 'Globe', network: 'Globe' },
    '0926': { carrier: 'Globe', network: 'Globe' },
    '0927': { carrier: 'Globe', network: 'Globe' },
    '0935': { carrier: 'Globe', network: 'Globe' },
    '0936': { carrier: 'Globe', network: 'Globe' },
    '0937': { carrier: 'Globe', network: 'Globe' },
    '0945': { carrier: 'Globe', network: 'Globe' },
    '0953': { carrier: 'Globe', network: 'Globe' },
    '0954': { carrier: 'Globe', network: 'Globe' },
    '0955': { carrier: 'Globe', network: 'Globe' },
    '0956': { carrier: 'Globe', network: 'Globe' },
    '0965': { carrier: 'Globe', network: 'Globe' },
    '0966': { carrier: 'Globe', network: 'Globe' },
    '0967': { carrier: 'Globe', network: 'Globe' },
    '0975': { carrier: 'Globe', network: 'Globe' },
    '0976': { carrier: 'Globe', network: 'Globe' },
    '0977': { carrier: 'Globe', network: 'Globe' },
    '0995': { carrier: 'Globe', network: 'Globe' },
    '0996': { carrier: 'Globe', network: 'Globe' },
    '0997': { carrier: 'Globe', network: 'Globe' },

    // TM (Touch Mobile - Globe subsidiary)
    // Most TM prefixes overlap with Globe, handled by Globe entries above

    // GOMO (Globe subsidiary)
    // GOMO prefixes overlap with Globe, handled by Globe entries above

    // DITO Telecommunity
    '0895': { carrier: 'DITO', network: 'DITO' },
    '0896': { carrier: 'DITO', network: 'DITO' },
    '0991': { carrier: 'DITO', network: 'DITO' },
    '0992': { carrier: 'DITO', network: 'DITO' },
    '0993': { carrier: 'DITO', network: 'DITO' },
    '0994': { carrier: 'DITO', network: 'DITO' },
};

/**
 * Identifies the carrier and network of a Philippine mobile number
 * @param phoneNumber - The phone number to identify (can include country code +63 or start with 0)
 * @returns CarrierInfo object with carrier name and network, or Unknown if not identified
 */
export function identifyCarrier(phoneNumber: string): CarrierInfo {
    // Remove spaces, dashes, and other non-numeric characters except +
    const cleaned = phoneNumber.replace(/[^\d+]/g, '');

    let prefix: string;

    // Handle different formats
    if (cleaned.startsWith('+63')) {
        // +639XXXXXXXXX format - convert to 09XXXXXXXXX
        prefix = '0' + cleaned.substring(3, 6);
    } else if (cleaned.startsWith('63')) {
        // 639XXXXXXXXX format - convert to 09XXXXXXXXX
        prefix = '0' + cleaned.substring(2, 5);
    } else if (cleaned.startsWith('0')) {
        // 09XXXXXXXXX format
        prefix = cleaned.substring(0, 4);
    } else if (cleaned.startsWith('9')) {
        // 9XXXXXXXXXX format - convert to 09XXXXXXXXX
        prefix = '0' + cleaned.substring(0, 3);
    } else {
        return { carrier: 'Unknown', network: 'Unknown' };
    }

    return (
        carrierPrefixes[prefix] || { carrier: 'Unknown', network: 'Unknown' }
    );
}

/**
 * Formats a phone number to standard Philippine format
 * @param phoneNumber - The phone number to format
 * @returns Formatted phone number (e.g., +63 917 123 4567)
 */
export function formatPhoneNumber(phoneNumber: string): string {
    const cleaned = phoneNumber.replace(/[^\d]/g, '');

    let normalized: string;

    if (cleaned.startsWith('63')) {
        normalized = cleaned;
    } else if (cleaned.startsWith('0')) {
        normalized = '63' + cleaned.substring(1);
    } else if (cleaned.startsWith('9')) {
        normalized = '63' + cleaned;
    } else {
        return phoneNumber; // Return original if can't parse
    }

    if (normalized.length !== 12) {
        return phoneNumber; // Return original if not valid length
    }

    // Format as +63 XXX XXX XXXX
    return `+${normalized.substring(0, 2)} ${normalized.substring(2, 5)} ${normalized.substring(5, 8)} ${normalized.substring(8)}`;
}

/**
 * Validates if a phone number is a valid Philippine mobile number
 * @param phoneNumber - The phone number to validate
 * @returns true if valid, false otherwise
 */
export function isValidPhilippineMobile(phoneNumber: string): boolean {
    const cleaned = phoneNumber.replace(/[^\d]/g, '');

    // Check different formats
    if (cleaned.startsWith('63') && cleaned.length === 12) {
        return true;
    } else if (cleaned.startsWith('0') && cleaned.length === 11) {
        return true;
    } else if (cleaned.startsWith('9') && cleaned.length === 10) {
        return true;
    }

    return false;
}

/**
 * Hook to identify and get information about a Philippine mobile number
 */
export function useIdentifyNumber(phoneNumber: string) {
    const carrierInfo = identifyCarrier(phoneNumber);
    const isValid = isValidPhilippineMobile(phoneNumber);
    const formatted = formatPhoneNumber(phoneNumber);

    return {
        ...carrierInfo,
        isValid,
        formatted,
        isSmartNetwork: carrierInfo.network === 'Smart',
        isGlobeNetwork: carrierInfo.network === 'Globe',
        isDITONetwork: carrierInfo.network === 'DITO',
    };
}

export default useIdentifyNumber;
