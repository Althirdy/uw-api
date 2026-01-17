import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { ScrollArea } from '@/components/ui/scroll-area';
import {
    Sheet,
    SheetContent,
    SheetHeader,
    SheetTitle,
    SheetTrigger,
} from '@/components/ui/sheet';
import {
    useFalseAlarmRealtime,
    FalseAlarmStats,
} from '@/hooks/use-false-alarm-realtime';
import {
    AlertTriangle,
    Camera,
    Clock,
    RefreshCw,
    TrendingUp,
    Wifi,
    WifiOff,
} from 'lucide-react';

interface DeviceBarProps {
    deviceName: string;
    count: number;
    percentage: number;
}

function DeviceBar({ deviceName, count, percentage }: DeviceBarProps) {
    return (
        <div className="space-y-1">
            <div className="flex items-center justify-between text-sm">
                <span className="truncate font-medium">{deviceName}</span>
                <span className="text-muted-foreground">
                    {count} ({percentage}%)
                </span>
            </div>
            <div className="bg-muted h-2 w-full overflow-hidden rounded-full">
                <div
                    className="bg-amber-500 h-full rounded-full transition-all duration-500"
                    style={{ width: `${percentage}%` }}
                />
            </div>
        </div>
    );
}

interface StatCardProps {
    title: string;
    value: string | number;
    icon: React.ReactNode;
    description?: string;
}

function StatCard({ title, value, icon, description }: StatCardProps) {
    return (
        <Card>
            <CardContent className="p-4">
                <div className="flex items-center justify-between">
                    <div className="flex-1">
                        <p className="text-muted-foreground mb-1 text-xs font-medium">
                            {title}
                        </p>
                        <p className="text-3xl font-bold">{value}</p>
                        {description && (
                            <p className="text-muted-foreground mt-1 text-xs">
                                {description}
                            </p>
                        )}
                    </div>
                    <div className="bg-amber-100 dark:bg-amber-950/50 rounded-full p-3">
                        {icon}
                    </div>
                </div>
            </CardContent>
        </Card>
    );
}

interface FalseAlarmItemProps {
    deviceName: string;
    locationName: string;
    reasoning: string;
    confidence: number | null;
    timeAgo: string;
    attemptedType: string | null;
}

function FalseAlarmItem({
    deviceName,
    locationName,
    reasoning,
    confidence,
    timeAgo,
    attemptedType,
}: FalseAlarmItemProps) {
    return (
        <div className="border-border hover:bg-muted/50 space-y-2.5 rounded-lg border p-4 transition-colors">
            <div className="flex items-start justify-between gap-2">
                <div className="flex items-center gap-2">
                    <AlertTriangle className="h-4 w-4 shrink-0 text-amber-500" />
                    <span className="text-sm font-semibold">{deviceName}</span>
                </div>
                {attemptedType && (
                    <Badge variant="outline" className="shrink-0 text-xs">
                        {attemptedType}
                    </Badge>
                )}
            </div>
            <p className="text-muted-foreground text-xs">{locationName}</p>
            <p className="line-clamp-2 text-sm leading-relaxed">{reasoning}</p>
            <div className="text-muted-foreground flex items-center justify-between pt-1 text-xs">
                <span className="flex items-center gap-1">
                    <Clock className="h-3 w-3" />
                    {timeAgo}
                </span>
                {confidence !== null && (
                    <span>Confidence: {confidence}%</span>
                )}
            </div>
        </div>
    );
}

export function FalseAlarmMonitor() {
    const { isConnected, stats, isLoading, todayCount, refetch } =
        useFalseAlarmRealtime();

    return (
        <Sheet>
            <SheetTrigger asChild>
                <Button
                    variant="outline"
                    size="sm"
                    className="relative gap-2 border-amber-200 bg-amber-50 text-amber-700 hover:bg-amber-100 hover:text-amber-800 dark:border-amber-800 dark:bg-amber-950/30 dark:text-amber-400 dark:hover:bg-amber-950/50"
                >
                    <AlertTriangle className="h-4 w-4" />
                    <span className="hidden sm:inline">False Alarms</span>
                    {todayCount > 0 && (
                        <Badge
                            variant="secondary"
                            className="bg-amber-500 text-white hover:bg-amber-600 ml-1 h-5 min-w-5 px-1.5"
                        >
                            {todayCount > 99 ? '99+' : todayCount}
                        </Badge>
                    )}
                </Button>
            </SheetTrigger>
            <SheetContent
                side="right"
                className="flex w-full flex-col gap-0 p-0 sm:max-w-md"
            >
                <SheetHeader className="border-b px-6 py-4">
                    <div className="flex items-center justify-between gap-4">
                        <SheetTitle className="flex items-center gap-2 text-lg">
                            <AlertTriangle className="h-5 w-5 text-amber-500" />
                            False Alarm Monitor
                        </SheetTitle>
                        <div className="flex items-center gap-2">
                            <Button
                                variant="ghost"
                                size="icon"
                                className="h-8 w-8"
                                onClick={refetch}
                                disabled={isLoading}
                            >
                                <RefreshCw
                                    className={`h-4 w-4 ${isLoading ? 'animate-spin' : ''}`}
                                />
                            </Button>
                            <Badge
                                variant={
                                    isConnected ? 'outline' : 'destructive'
                                }
                                className={`gap-1 ${isConnected ? 'border-green-500 text-green-600' : ''}`}
                            >
                                {isConnected ? (
                                    <Wifi className="h-3 w-3" />
                                ) : (
                                    <WifiOff className="h-3 w-3" />
                                )}
                            </Badge>
                        </div>
                    </div>
                </SheetHeader>

                <ScrollArea className="flex-1 px-6">
                    <div className="space-y-6 py-6">
                        {/* Statistics Cards */}
                        <div>
                            <h3 className="text-muted-foreground mb-4 text-xs font-semibold uppercase tracking-wider">
                                Today's Statistics
                            </h3>
                            <div className="grid grid-cols-2 gap-4">
                                <StatCard
                                    title="Total Today"
                                    value={stats?.statistics.today ?? 0}
                                    icon={
                                        <AlertTriangle className="h-4 w-4 text-amber-600" />
                                    }
                                />
                                <StatCard
                                    title="This Hour"
                                    value={stats?.statistics.this_hour ?? 0}
                                    icon={
                                        <Clock className="h-4 w-4 text-amber-600" />
                                    }
                                />
                                <StatCard
                                    title="This Week"
                                    value={stats?.statistics.this_week ?? 0}
                                    icon={
                                        <TrendingUp className="h-4 w-4 text-amber-600" />
                                    }
                                />
                                <StatCard
                                    title="Peak Hour"
                                    value={
                                        stats?.statistics.peak_hour?.formatted ??
                                        '--:--'
                                    }
                                    icon={
                                        <Camera className="h-4 w-4 text-amber-600" />
                                    }
                                    description={
                                        stats?.statistics.peak_hour
                                            ? `${stats.statistics.peak_hour.count} detections`
                                            : undefined
                                    }
                                />
                            </div>
                        </div>

                        {/* Device Breakdown */}
                        {stats?.device_breakdown &&
                            stats.device_breakdown.length > 0 && (
                                <div>
                                    <h3 className="text-muted-foreground mb-4 text-xs font-semibold uppercase tracking-wider">
                                        By Camera Device
                                    </h3>
                                    <Card>
                                        <CardContent className="space-y-4 p-4">
                                            {stats.device_breakdown.map(
                                                (device) => (
                                                    <DeviceBar
                                                        key={device.device_id}
                                                        deviceName={
                                                            device.device_name
                                                        }
                                                        count={device.count}
                                                        percentage={
                                                            device.percentage
                                                        }
                                                    />
                                                ),
                                            )}
                                        </CardContent>
                                    </Card>
                                </div>
                            )}

                        {/* Recent False Alarms */}
                        <div>
                            <h3 className="text-muted-foreground mb-4 text-xs font-semibold uppercase tracking-wider">
                                Recent False Alarms
                            </h3>
                            <div className="space-y-3">
                                {isLoading ? (
                                    <div className="text-muted-foreground flex items-center justify-center py-8 text-sm">
                                        <RefreshCw className="mr-2 h-4 w-4 animate-spin" />
                                        Loading...
                                    </div>
                                ) : stats?.recent_false_alarms &&
                                  stats.recent_false_alarms.length > 0 ? (
                                    stats.recent_false_alarms.map(
                                        (falseAlarm) => (
                                            <FalseAlarmItem
                                                key={falseAlarm.id}
                                                deviceName={
                                                    falseAlarm.device_name
                                                }
                                                locationName={
                                                    falseAlarm.location_name
                                                }
                                                reasoning={
                                                    falseAlarm.gemini_reasoning
                                                }
                                                confidence={
                                                    falseAlarm.confidence_score
                                                }
                                                timeAgo={falseAlarm.time_ago}
                                                attemptedType={
                                                    falseAlarm.attempted_accident_type
                                                }
                                            />
                                        ),
                                    )
                                ) : (
                                    <div className="text-muted-foreground py-8 text-center text-sm">
                                        <AlertTriangle className="mx-auto mb-2 h-8 w-8 opacity-50" />
                                        <p>No false alarms yet today</p>
                                        <p className="text-xs">
                                            YOLO detections are working
                                            correctly
                                        </p>
                                    </div>
                                )}
                            </div>
                        </div>
                    </div>
                </ScrollArea>

                {/* Footer */}
                <div className="border-t px-6 py-4">
                    <p className="text-muted-foreground flex items-center justify-center gap-1.5 text-xs">
                        <span
                            className={`inline-block h-2 w-2 rounded-full ${isConnected ? 'animate-pulse bg-green-500' : 'bg-red-500'}`}
                        />
                        {isConnected ? 'Live updates enabled' : 'Reconnecting...'}
                    </p>
                </div>
            </SheetContent>
        </Sheet>
    );
}

export default FalseAlarmMonitor;
