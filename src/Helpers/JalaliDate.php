<?php

declare(strict_types=1);

namespace jamal13647850\wphelpers\Helpers;

/**
 * JalaliDate: ابزار تاریخ هجری شمسی (جلالی) برای PHP
 * الهام‌گرفته از JDF توسط رضا غلام‌پناهی
 *
 * @link   https://jdf.scr.ir
 * @author Reza Gholampanahi
 * @copyright GPL-2.0-or-later
 * @version 2.80 (2024)
 */
class JalaliDate
{
    /**
     * فرمت‌دهی تاریخ طبق تقویم جلالی (معادل jdate)
     *
     * @param string $format
     * @param int|string|null $timestamp(time())
     * @param string $time_zone
     * @param string $tr_num fa|en
     * @return string
     */
    public static function format(
        string $format,
        $timestamp = null,
        string $time_zone = 'Asia/Tehran',
        string $tr_num = 'fa'
    ): string {
        $timestamp = $timestamp === null
            ? time()
            : (is_numeric($timestamp) ? (int)$timestamp : (int)self::trNum((string)$timestamp, 'en'));
        if ($time_zone !== 'local') {
            date_default_timezone_set($time_zone !== '' ? $time_zone : 'Asia/Tehran');
        }
        // H_i_j_n_O_P_s_w_Y
        $date = explode('_', date('H_i_j_n_O_P_s_w_Y', $timestamp));
        [$jy, $jm, $jd] = self::gregorianToJalali((int)$date[8], (int)$date[3], (int)$date[2]);
        $doy = ($jm < 7) ? (($jm - 1) * 31) + $jd - 1 : (($jm - 7) * 30) + $jd + 185;
        $kab = (((($jy + 12) % 33) % 4) === 1) ? 1 : 0;

        $out = '';
        $sl = strlen($format);

        for ($i = 0; $i < $sl; $i++) {
            $sub = substr($format, $i, 1);
            if ($sub === '\\') {
                $out .= substr($format, ++$i, 1);
                continue;
            }
            switch ($sub) {
                // پرکاربردترین کدهای جلالی
                case 'Y': $out .= $jy; break;
                case 'y': $out .= substr((string)$jy, 2, 2); break;
                case 'm': $out .= ($jm < 10 ? '0' : '') . $jm; break;
                case 'n': $out .= $jm; break;
                case 'd': $out .= ($jd < 10 ? '0' : '') . $jd; break;
                case 'j': $out .= $jd; break;
                case 'F': $out .= self::jdateWords(['mm' => $jm], ' '); break;
                case 'l': $out .= self::jdateWords(['rh' => $date[7]], ' '); break;
                case 'D': $out .= self::jdateWords(['kh' => $date[7]], ' '); break;
                case 'H': $out .= $date[0]; break;
                case 'i': $out .= $date[1]; break;
                case 's': $out .= $date[6]; break;
                case 'N': $out .= $date[7] + 1; break;
                case 'w': $out .= ($date[7] == 6) ? 0 : $date[7] + 1; break;
                case 'z': $out .= $doy; break;
                case 'L': $out .= $kab; break;
                case 't': $out .= ($jm != 12) ? (31 - (int)($jm / 6.5)) : ($kab + 29); break;
                case 'U': $out .= $timestamp; break;
                case 'a': $out .= ($date[0] < 12) ? 'ق.ظ' : 'ب.ظ'; break;
                case 'A': $out .= ($date[0] < 12) ? 'قبل از ظهر' : 'بعد از ظهر'; break;
                default : $out .= $sub; // سایر موارد؛ یا در صورت نیاز گسترش دهید
            }
        }

        // تبدیل عددها در صورت نیاز
        return $tr_num !== 'en' ? self::trNum($out, 'fa') : $out;
    }

    /**
     * تبدیل میلادی به جلالی
     * @param int $gy
     * @param int $gm
     * @param int $gd
     * @return array [year, month, day]
     */
    public static function gregorianToJalali(int $gy, int $gm, int $gd): array
    {
        $g_d_m = [0, 31, 59, 90, 120, 151, 181, 212, 243, 273, 304, 334];
        $gy2 = ($gm > 2) ? $gy + 1 : $gy;
        $days = 355666 + (365 * $gy)
            + (int)(($gy2 + 3) / 4)
            - (int)(($gy2 + 99) / 100)
            + (int)(($gy2 + 399) / 400)
            + $gd + $g_d_m[$gm - 1];
        $jy = -1595 + (33 * (int)($days / 12053));
        $days %= 12053;
        $jy += 4 * (int)($days / 1461);
        $days %= 1461;
        if ($days > 365) {
            $jy += (int)(($days - 1) / 365);
            $days = ($days - 1) % 365;
        }
        if ($days < 186) {
            $jm = 1 + (int)($days / 31);
            $jd = 1 + ($days % 31);
        } else {
            $jm = 7 + (int)(($days - 186) / 30);
            $jd = 1 + (($days - 186) % 30);
        }
        return [$jy, $jm, $jd];
    }

    /**
     * تبدیل جلالی به میلادی
     * @param int $jy
     * @param int $jm
     * @param int $jd
     * @return array [year, month, day]
     */
    public static function jalaliToGregorian(int $jy, int $jm, int $jd): array
    {
        $jy += 1595;
        $days = -355668 + (365 * $jy)
            + (((int)($jy / 33)) * 8)
            + ((int)((($jy % 33) + 3) / 4))
            + $jd
            + (($jm < 7)
                ? ($jm - 1) * 31
                : (($jm - 7) * 30 + 186)
            );
        $gy = 400 * (int)($days / 146097);
        $days %= 146097;
        if ($days > 36524) {
            $gy += 100 * (int)(--$days / 36524);
            $days %= 36524;
            if ($days >= 365) $days++;
        }
        $gy += 4 * (int)($days / 1461);
        $days %= 1461;
        if ($days > 365) {
            $gy += (int)(($days - 1) / 365);
            $days = ($days - 1) % 365;
        }
        $gd = $days + 1;
        $sal_a = [0, 31, (($gy % 4 == 0 && $gy % 100 != 0) || ($gy % 400 == 0)) ? 29 : 28,
            31, 30, 31, 30, 31, 31, 30, 31, 30, 31];
        for ($gm = 0; $gm < 13 && $gd > $sal_a[$gm]; $gm++) {
            $gd -= $sal_a[$gm];
        }
        return [$gy, $gm, $gd];
    }

    /**
     * تبدیل ارقام فارسی ↔️ انگلیسی
     *
     * @param string $str
     * @param string $mod 'fa'|'en'
     * @param string $mf
     * @return string
     */
    public static function trNum(string $str, string $mod = 'en', string $mf = '٫'): string
    {
        $num_a = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9', '.'];
        $key_a = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹', $mf];
        return $mod === 'fa'
            ? str_replace($num_a, $key_a, $str)
            : str_replace($key_a, $num_a, $str);
    }

    /**
     * تبدیل شماره یا ماه و روز و ... به واژه فارسی
     *
     * @param array $array
     * @param string $mod
     * @return string|array
     */
    public static function jdateWords(array $array, string $mod = '')
    {
        foreach ($array as $type => $num) {
            $num = (int)self::trNum((string)$num, 'en');
            switch ($type) {
                case 'mm': // نام کامل ماه
                    $key = ['فروردین', 'اردیبهشت', 'خرداد', 'تیر', 'مرداد', 'شهریور', 'مهر',
                        'آبان', 'آذر', 'دی', 'بهمن', 'اسفند'];
                    $array[$type] = $key[$num - 1];
                    break;
                case 'rr': // عدد روز به حروف
                    $key = ['یک', 'دو', 'سه', 'چهار', 'پنج', 'شش', 'هفت', 'هشت', 'نه', 'ده',
                        'یازده', 'دوازده', 'سیزده', 'چهارده', 'پانزده', 'شانزده', 'هفده', 'هجده', 'نوزده',
                        'بیست', 'بیست و یک', 'بیست و دو', 'بیست و سه', 'بیست و چهار', 'بیست و پنج', 'بیست و شش',
                        'بیست و هفت', 'بیست و هشت', 'بیست و نه', 'سی', 'سی و یک'];
                    $array[$type] = $key[$num - 1];
                    break;
                case 'rh': // نام کامل روز هفته
                    $key = ['یکشنبه', 'دوشنبه', 'سه شنبه', 'چهارشنبه', 'پنجشنبه', 'جمعه', 'شنبه'];
                    $array[$type] = $key[$num];
                    break;
                case 'kh': // حرف اختصاری روز هفته
                    $key = ['ی', 'د', 'س', 'چ', 'پ', 'ج', 'ش'];
                    $array[$type] = $key[$num];
                    break;
                default:
                    $array[$type] = $num;
            }
        }
        return ($mod === '') ? $array : implode($mod, $array);
    }

    /**
     * چک کردن اعتبار تاریخ جلالی
     * @param int $jm ماه
     * @param int $jd روز
     * @param int $jy سال
     * @return bool
     */
    public static function isValid(int $jm, int $jd, int $jy): bool
    {
        $l_d = ($jm == 12 && ((($jy + 12) % 33) % 4) != 1)
            ? 29 : (31 - (int)($jm / 6.5));
        return ($jm > 12 || $jd > $l_d || $jm < 1 || $jd < 1 || $jy < 1)
            ? false : true;
    }

    /**
     * jgetdate - مشابه getdate میلادی، اما جلالی
     *
     * @param int|null $timestamp
     * @param string $time_zone
     * @param string $tr_num
     * @return array
     */
    public static function getDate(?int $timestamp = null, string $time_zone = 'Asia/Tehran', string $tr_num = 'en'): array
    {
        $timestamp = $timestamp ?? time();
        $jdate = explode(
            '_',
            self::format('F_G_i_j_l_n_s_w_Y_z', $timestamp, $time_zone, $tr_num)
        );
        return [
            'seconds'  => (int)self::trNum($jdate[6], $tr_num),
            'minutes'  => (int)self::trNum($jdate[2], $tr_num),
            'hours'    => $jdate[1],
            'mday'     => $jdate[3],
            'wday'     => $jdate[7],
            'mon'      => $jdate[5],
            'year'     => $jdate[8],
            'yday'     => $jdate[9],
            'weekday'  => $jdate[4],
            'month'    => $jdate[0],
            0          => self::trNum((string)$timestamp, $tr_num)
        ];
    }
}