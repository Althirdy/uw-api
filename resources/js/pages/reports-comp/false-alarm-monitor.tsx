import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { ScrollArea } from '@/components/ui/scroll-area';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import {
    useFalseAlarmRealtime,
} from '@/hooks/use-false-alarm-realtime';
import {
    AlertTriangle,
    RefreshCw,
    ChevronDown,
    ChevronUp,
} from 'lucide-react';
import { useState } from 'react';

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
    const [isExpanded, setIsExpanded] = useState(false);
    const shouldShowToggle = reasoning.length > 150;

    return (
        <div className="border-border hover:bg-muted/50 space-y-3 rounded-lg border p-4 transition-colors">
            <div className="flex items-start justify-between gap-2">
                <div className="flex-1">
                    <div className="flex items-center gap-2 mb-1">
                        <span className="text-xs font-medium text-muted-foreground">Device:</span>
                        <span className="text-sm font-semibold">{deviceName}</span>
                    </div>
                    <div className="flex items-center gap-2">
                        <span className="text-xs font-medium text-muted-foreground">Location:</span>
                        <span className="text-xs text-muted-foreground">{locationName}</span>
                    </div>
                </div>
                {attemptedType && (
                    <Badge variant="outline" className="shrink-0 text-xs">
                        {attemptedType}
                    </Badge>
                )}
            </div>
            
            <div className="space-y-2">
                <span className="text-xs font-medium text-muted-foreground">Reasoning:</span>
                <p className={`text-sm leading-relaxed ${!isExpanded && shouldShowToggle ? 'line-clamp-2' : ''}`}>
                    {reasoning}
                </p>
                {shouldShowToggle && (
                    <Button
                        variant="ghost"
                        size="sm"
                        onClick={() => setIsExpanded(!isExpanded)}
                        className="h-auto p-0 text-xs text-primary hover:bg-transparent"
                    >
                        {isExpanded ? (
                            <>
                                <span>Show less</span>
                                <ChevronUp className="ml-1 h-3 w-3" />
                            </>
                        ) : (
                            <>
                                <span>See more</span>
                                <ChevronDown className="ml-1 h-3 w-3" />
                            </>
                        )}
                    </Button>
                )}
            </div>

            <div className="text-muted-foreground flex items-center justify-between pt-1 text-xs border-t">
                <span>{timeAgo}</span>
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
        <Dialog>
            <DialogTrigger asChild>
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
            </DialogTrigger>
            <DialogContent className="flex max-h-[85vh] max-w-2xl flex-col gap-0 overflow-hidden p-0">
                <DialogHeader className="flex-shrink-0 border-b px-6 py-4">
                    <div className="flex items-center justify-between gap-4">
                        <div>
                            <DialogTitle className="text-lg">
                                False Alarm Monitor
                            </DialogTitle>
                            <p className="text-xs text-muted-foreground mt-1">
                                Showing today's false alarms only
                            </p>
                        </div>
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
                                className={`${isConnected ? 'border-green-500 text-green-600' : ''}`}
                            >
                                {isConnected ? 'Connected' : 'Disconnected'}
                            </Badge>
                        </div>
                    </div>
                </DialogHeader>

                <ScrollArea className="flex-1 overflow-y-auto">
                    <div className="px-6 py-4">
                        {/* Recent False Alarms */}
                        <div>
                            <div className="flex items-center justify-between mb-4">
                                <h3 className="text-muted-foreground text-xs font-semibold uppercase tracking-wider">
                                    Today's False Alarms
                                </h3>
                                {stats?.recent_false_alarms && stats.recent_false_alarms.length > 0 && (
                                    <Badge variant="secondary" className="text-xs">
                                        {stats.recent_false_alarms.length} records
                                    </Badge>
                                )}
                            </div>
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
                                        <p className="font-medium">No false alarms yet today</p>
                                        <p className="text-xs mt-1">
                                            YOLO detections are working correctly
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
            </DialogContent>
        </Dialog>
    );
}

export default FalseAlarmMonitor;
