
#include <time.h>
#include <opencv2/opencv.hpp>
#include <iostream>
using namespace std;

IplImage* preGet(const char* path);

IplImage* figureFace(IplImage* image);

IplImage* fillFace(IplImage* content, const char* templatePath);

IplImage* cutNeed(IplImage* src, CvRect r);

int main(int argc, char* argv[])
{
	char savedPath[64] = { 0 };
	const char* windowTitle = "first cv";
	IplImage* pImage = preGet(argv[1]);
	IplImage* filled = fillFace(figureFace(pImage), "../template/template.jpg");
	sprintf(savedPath, "../../scorgen/%d.jpg", time(0));
	cvSaveImage(savedPath, filled);
	cvReleaseImage(&filled);
	cout << savedPath+14 << endl;
	cvReleaseImage(&pImage);
	return 0;
}


IplImage* cutNeed(IplImage* src, CvRect r){
	cvSetImageROI(src, r);
	IplImage* result = cvCreateImage(CvSize(r.width, r.height), src->depth, src->nChannels);
	cvCopy(src, result, 0);
	cvResetImageROI(src);
	return result;
}

IplImage* fillFace(IplImage* content, const char* templatePath){
	IplImage* temp = cvLoadImage(templatePath, CV_LOAD_IMAGE_GRAYSCALE);
	cvSetImageROI(temp, CvRect(40, 20, 80, 80));
	IplImage* grayImage = cvCreateImage(cvGetSize(content), 8, 1);
	cvCvtColor(content, grayImage, CV_RGB2GRAY);
	IplImage* partialed = cvCreateImage(cvGetSize(grayImage), 8, 1);
	cvThreshold(grayImage, partialed, 100, 255, CV_THRESH_BINARY);
	IplImage* scaled = cvCreateImage(CvSize(80, 80), 8, 1);
	cvResize(partialed, scaled, CV_INTER_AREA);
	cvCopy(scaled, temp, 0);
	cvResetImageROI(temp);
	return temp;
}

IplImage* preGet(const char* imgPath){
	IplImage* pImage = cvLoadImage(imgPath);
	double after = 480;
	if (pImage->width > 640 || pImage->height > 640){
		if (pImage->width > pImage->height){
			double scale = after / pImage->width;
			int afterHeight = scale * pImage->height;
			IplImage* resultWidth = cvCreateImage(CvSize(after, afterHeight), pImage->depth, pImage->nChannels);
			cvResize(pImage, resultWidth, CV_INTER_AREA);
			return resultWidth;
		}
		else{
			double scaleH = after / pImage->height;
			int afterWidth = scaleH * pImage->width;
			IplImage* resultHeight = cvCreateImage(CvSize(afterWidth, after), pImage->depth, pImage->nChannels);
			cvResize(pImage, resultHeight, CV_INTER_AREA);
			return resultHeight;
		}
	}
	return pImage;
}

IplImage* figureFace(IplImage* image){
	CvHaarClassifierCascade* classifier = (CvHaarClassifierCascade*)cvLoad("../cpp/haarcascade_frontalface_alt2.xml");
	CvMemStorage* storage = cvCreateMemStorage(0);
	cvClearMemStorage(storage);
	CvSeq* seqFaced = cvHaarDetectObjects(image, classifier, storage);
	return cutNeed(image, *(CvRect*)cvGetSeqElem(seqFaced, 0));
}


void skinPart(IplImage* src){
	IplImage* ycb = cvCreateImage(cvGetSize(src), 8, 3);
	cvCvtColor(src, ycb, CV_RGB2YCrCb);
	for (int i = 0; i < ycb->height; i++){
		for (int j = 0; j < ycb->width; j++){
			CvScalar s = cvGet2D(ycb, i, j);
			if (s.val[1] >= 109 && s.val[1] <= 140 && s.val[2] >= 135 && s.val[2] <= 158)
				cvSet2D(src, i, j, CvScalar(0, 0, 0, 0));
		}
	}
}

