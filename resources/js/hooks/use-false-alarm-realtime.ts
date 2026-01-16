import { useCallback, useEffect, useState } from 'react';
import echo from '@/lib/echo';
import { useToast } from '@/components';

export interface FalseAlarmData {
    id: number;
    device_name: string;
    location_name: string;
    attempted_accident_type: string | null;
    gemini_reasoning: string;
    confidence_score: number | null;
    detected_objects: string[];
    detected_at: string;
    created_at: string;
}

export interface FalseAlarmStats {
    statistics: {
        today: number;
        this_week: number;
        this_hour: number;
        peak_hour: {
            hour: number;
            count: number;
            formatted: string;
        } | null;
    };
    device_breakdown: {
        device_id: number;
        device_name: string;
        count: number;
        percentage: number;
    }[];
    recent_false_alarms: (FalseAlarmData & { time_ago: string })[];
}

export function useFalseAlarmRealtime() {
    const [isConnected, setIsConnected] = useState(false);
    const [stats, setStats] = useState<FalseAlarmStats | null>(null);
    const [isLoading, setIsLoading] = useState(true);
    const [todayCount, setTodayCount] = useState(0);
    const { toast } = useToast();

    // Fetch stats from API
    const fetchStats = useCallback(async () => {
        try {
            const response = await fetch('/api/v1/yolo/false-alarms/stats');
            const data = await response.json();
            if (data.success) {
                setStats(data.data);
                setTodayCount(data.data.statistics.today);
            }
        } catch (error) {
            console.error('Failed to fetch false alarm stats:', error);
        } finally {
            setIsLoading(false);
        }
    }, []);

    useEffect(() => {
        // Initial fetch
        fetchStats();

        // Listen to false-alarms channel
        const channel = echo.channel('false-alarms');

        channel
            .listen('.false-alarm.detected', (data: FalseAlarmData) => {
                console.log('⚠️ False alarm detected:', data);

                // Show toast notification
                toast({
                    title: '⚠️ False Alarm Detected',
                    description: `${data.device_name}: ${data.gemini_reasoning.substring(0, 100)}...`,
                    variant: 'default',
                });

                // Update local count immediately
                setTodayCount((prev) => prev + 1);

                // Refetch stats to get updated data
                fetchStats();
            })
            .subscribed(() => {
                setIsConnected(true);
            })
            .error((error: unknown) => {
                console.error('❌ False alarm channel error:', error);
                setIsConnected(false);
            });

        // Cleanup on unmount
        return () => {
            echo.leaveChannel('false-alarms');
        };
    }, [toast, fetchStats]);

    return { isConnected, stats, isLoading, todayCount, refetch: fetchStats };
}
